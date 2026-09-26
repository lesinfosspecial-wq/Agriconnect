@extends('layouts.admin')

@section('title', 'Ventes — Agriconnect')
@section('heading', 'Ventes')
@section('lead', $transactions->count().' réservation(s) suivie(s)')

@section('main')
    <div class="sheet">
        <table class="adm-table">
            <thead>
                <tr><th>Commande</th><th>Vendeur</th><th>Quantité</th><th>Montant</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    @php
                        $ton = match ($transaction->status) {
                            'terminee', 'collectee' => 'ok',
                            'annulee', 'expiree' => 'bad',
                            'en_collecte', 'en_preparation' => 'ship',
                            default => 'wait',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="who">
                                <img src="{{ $transaction->stock->product->visuel() }}" alt="">
                                <div>
                                    <strong>{{ $transaction->stock->product->name }}</strong>
                                    <small>{{ $transaction->client }}@if ($transaction->canal === 'sur_place') · Sur place @endif</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $transaction->stock->seller->name }}</td>
                        <td>{{ number_format($transaction->quantity, 1, ',', ' ') }} {{ $transaction->stock->unit }}</td>
                        <td class="num">{{ fcfa($transaction->quantity * $transaction->unit_price) }}</td>
                        <td><span class="pill pill-{{ $ton }}">{{ \App\Support\Libelles::commande($transaction->status) }}</span></td>
                        <td class="acts">
                            <a class="btn-quiet" href="{{ route('admin.transactions.show', $transaction) }}">Détail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Aucune vente.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
