@extends('layouts.admin')

@section('title', 'Stocks — Agriconnect')
@section('heading', 'Stocks')
@section('lead', $stocks->count().' annonce(s) · une suspension retire le stock des acheteurs')

@section('main')
    <div class="sheet">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Lieu</th>
                    <th>Disponible</th>
                    <th>Prix</th>
                    <th>Prix IA</th>
                    <th>Urgence</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    @php
                        $tonStatut = match ($stock->status) {
                            'publie' => 'ok',
                            'suspendu', 'rejete', 'annule', 'expire' => 'bad',
                            'vendu' => 'done',
                            default => 'wait',
                        };
                        $tonUrgence = match ($stock->urgency) {
                            'urgent' => 'bad',
                            'a_ecouler' => 'wait',
                            default => 'ok',
                        };
                        $clos = in_array($stock->status, ['vendu', 'expire', 'annule'], true);
                    @endphp
                    <tr>
                        <td>
                            <div class="who">
                                <img src="{{ $stock->photo_path ? asset('storage/'.$stock->photo_path) : $stock->product->visuel() }}" alt="">
                                <div>
                                    <strong>{{ $stock->product->name }}</strong>
                                    <small>{{ $stock->seller->name }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $stock->quartier }}</td>
                        <td>{{ number_format($stock->availableQuantity(), 0, ',', ' ') }} / {{ number_format($stock->quantity, 0, ',', ' ') }} {{ $stock->unit }}</td>
                        <td class="num">{{ fcfa($stock->seller_price) }}</td>
                        <td>{{ fcfa($stock->ai_price) }}</td>
                        <td><span class="pill pill-{{ $tonUrgence }}">{{ \App\Support\Libelles::urgence($stock->urgency) }}</span></td>
                        <td><span class="pill pill-{{ $tonStatut }}">{{ \App\Support\Libelles::statutStock($stock->status) }}</span></td>
                        <td class="acts">
                            @unless ($clos)
                                <form method="POST" action="{{ route('admin.annonces.suspendre', $stock) }}">
                                    @csrf
                                    <button class="btn-quiet {{ $stock->status === 'suspendu' ? 'is-ok' : 'is-stop' }}" type="submit">
                                        {{ $stock->status === 'suspendu' ? 'Réactiver' : 'Suspendre' }}
                                    </button>
                                </form>
                            @endunless
                            <a class="btn-quiet" href="{{ route('admin.annonces.show', $stock) }}">Détail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">Aucun stock.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
