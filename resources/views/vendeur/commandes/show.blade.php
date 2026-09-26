@extends('layouts.producteur')

@section('title', 'Commande — Agriconnect')

@section('main')
    <a class="detail-link back-link" href="{{ route('vendeur.commandes') }}">Retour aux commandes</a>
    <div class="catalog-head">
        <div>
            <h1>{{ $commande->stock->product->name }}</h1>
            <p>#AG-{{ str_pad((string) $commande->id, 4, '0', STR_PAD_LEFT) }} · {{ $commande->buyer->name }}</p>
        </div>
        <span class="pill pill-ok">{{ \App\Support\Libelles::commande($commande->status) }}</span>
    </div>

    <div class="sheet fiche">
        <ul>
            <li><span>Acheteur</span><b>{{ $commande->buyer->name }} · {{ $commande->buyer->phone }}</b></li>
            <li><span>Quantité</span><b>{{ number_format($commande->quantity, 0, ',', ' ') }} {{ $commande->stock->unit }}</b></li>
            <li><span>Montant</span><b>{{ fcfa($commande->quantity * $commande->unit_price) }}</b></li>
            <li><span>Rendez-vous</span><b>{{ $commande->meetup_point }}</b></li>
            <li><span>Heure</span><b>{{ $commande->pickup_at?->translatedFormat('d/m/Y H:i') ?? 'Non fixée' }}</b></li>
            <li><span>Lieu du stock</span><b>{{ $commande->stock->quartier }}</b></li>
        </ul>
        @if ($action = \App\Support\Libelles::etapeCommande($commande->status))
            <form method="POST" action="{{ route('vendeur.commandes.avancer', $commande) }}">
                @csrf
                <button class="step-btn" type="submit">{{ $action }}</button>
            </form>
        @endif
    </div>
@endsection
