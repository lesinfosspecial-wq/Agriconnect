@extends('layouts.admin')

@section('title', 'Vente — Agriconnect')
@section('heading', $transaction->stock->product->name)
@section('lead', \App\Support\Libelles::commande($transaction->status))

@section('main')
    <a class="back" href="{{ route('admin.transactions') }}">Retour aux ventes</a>
    <section class="board board-split">
        <article class="price-hero">
            <img src="{{ $transaction->stock->product->visuel() }}" alt="">
            <div>
                <p>Montant</p>
                <strong>{{ fcfa($transaction->quantity * $transaction->unit_price) }}</strong>
                <small>{{ number_format($transaction->quantity, 1, ',', ' ') }} {{ $transaction->stock->unit }} × {{ fcfa($transaction->unit_price) }}</small>
            </div>
        </article>
        <article class="panel-card facts">
            <header><h2>Rendez-vous</h2></header>
            <ul>
                <li>
                    <span>Acheteur</span>
                    <b>
                        @if ($transaction->buyer)
                            <a href="{{ route('admin.identites.show', $transaction->buyer) }}">{{ $transaction->client }}</a>
                        @else
                            {{ $transaction->client }} · Sur place
                        @endif
                    </b>
                </li>
                <li><span>Vendeur</span><b><a href="{{ route('admin.identites.show', $transaction->stock->seller) }}">{{ $transaction->stock->seller->name }}</a></b></li>
                <li><span>Annonce</span><b><a href="{{ route('admin.annonces.show', $transaction->stock) }}">{{ $transaction->stock->product->name }} · {{ $transaction->stock->quartier }}</a></b></li>
                <li><span>Lieu</span><b>{{ $transaction->meetup_point }}</b></li>
                <li><span>Distance</span><b>{{ km($transaction->distance_km) }}</b></li>
                <li><span>Passage</span><b>{{ $transaction->pickup_at?->translatedFormat('d F Y, H:i') }}</b></li>
                @if ($transaction->rating)
                    <li><span>Note</span><b>{{ $transaction->rating->note }}/5</b></li>
                @endif
            </ul>
        </article>
    </section>
@endsection
