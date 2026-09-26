@extends('layouts.producteur')

@section('title', 'Vente — Agriconnect')

@section('main')
    <a class="detail-link back-link" href="{{ route('vendeur.ventes') }}">Retour aux ventes</a>
    <div class="catalog-head">
        <div>
            <h1>{{ $vente->stock->product->name }}</h1>
            <p>#AG-{{ str_pad((string) $vente->id, 4, '0', STR_PAD_LEFT) }} · {{ $vente->client }}</p>
        </div>
        @if ($vente->canal === 'sur_place')
            <span class="pill pill-wait">Sur place</span>
        @else
            <span class="pill pill-dispo">Appli</span>
        @endif
    </div>

    <div class="sheet fiche">
        <ul>
            <li>
                <span>Client</span>
                <b>
                    {{ $vente->client }}
                    @if ($vente->buyer)
                        · {{ $vente->buyer->phone }}
                    @endif
                </b>
            </li>
            <li><span>Quantité</span><b>{{ number_format($vente->quantity, 1, ',', ' ') }} {{ $vente->stock->unit }}</b></li>
            <li><span>Prix unitaire</span><b>{{ fcfa($vente->unit_price) }} / {{ $vente->stock->unit }}</b></li>
            <li><span>Montant</span><b>{{ fcfa($vente->quantity * $vente->unit_price) }}</b></li>
            <li><span>Date</span><b>{{ $vente->pickup_at?->translatedFormat('d/m/Y H:i') ?? $vente->created_at?->translatedFormat('d/m/Y H:i') }}</b></li>
            <li><span>Rendez-vous</span><b>{{ $vente->canal === 'sur_place' ? 'Passage à la ferme' : $vente->meetup_point }}</b></li>
            <li><span>Lieu du stock</span><b>{{ $vente->stock->quartier }}</b></li>
            <li><span>Statut</span><b>{{ \App\Support\Libelles::commande($vente->status) }}</b></li>
        </ul>
    </div>
@endsection
