@extends('layouts.acheteur')

@section('title', 'Commande — Agriconnect')

@section('main')
    @php
        $qte = (float) $commande->quantity;
        $qteAffichee = fmod($qte, 1.0) === 0.0 ? number_format($qte, 0, ',', ' ') : number_format($qte, 1, ',', ' ');
    @endphp
    <a class="back" href="{{ route('acheteur.reservations.index') }}">Retour aux commandes</a>
    <section class="detail">
        <div class="detail-photo">
            <img src="{{ $commande->stock->product->visuel() }}" alt="">
        </div>
        <div class="detail-info">
            <span class="badge">{{ \App\Support\Libelles::commande($commande->status) }}</span>
            <h1>{{ $commande->stock->product->name }}</h1>
            <p class="big-price">{{ fcfa($commande->quantity * $commande->unit_price) }}</p>
            <div class="tiles">
                <div><span>Quantité</span><strong>{{ $qteAffichee }} {{ $commande->stock->unit }}</strong></div>
                <div><span>Prix</span><strong>{{ fcfa($commande->unit_price) }} / {{ $commande->stock->unit }}</strong></div>
                <div><span>Producteur</span><strong>{{ $commande->stock->seller->name }}</strong></div>
                <div><span>Téléphone</span><strong>{{ $commande->stock->seller->phone }}</strong></div>
                <div><span>Rendez-vous</span><strong>{{ $commande->meetup_point }}</strong></div>
                <div><span>Passage</span><strong>{{ $commande->pickup_at?->translatedFormat('d F Y, H:i') }}</strong></div>
            </div>
            @if ($commande->statut === 'reservee')
                <form method="POST" action="{{ route('acheteur.reservations.annuler', $commande) }}">
                    @csrf
                    <button class="step-btn stop" type="submit">Annuler la réservation</button>
                </form>
            @endif
            @if ($commande->peutEtreNotee())
                <form class="rate" method="POST" action="{{ route('acheteur.reservations.noter', $commande) }}">
                    @csrf
                    <span>Votre avis</span>
                    <div class="stars">
                        @for ($i = 5; $i >= 1; $i--)
                            <input type="radio" name="note" id="note-{{ $commande->id }}-{{ $i }}" value="{{ $i }}" @checked($i === 5) required>
                            <label for="note-{{ $commande->id }}-{{ $i }}">★</label>
                        @endfor
                    </div>
                    <input name="commentaire" maxlength="500" placeholder="Un mot sur le producteur, si vous voulez">
                    <button class="step-btn" type="submit">Envoyer la note</button>
                </form>
            @elseif ($commande->rating)
                <p class="noted">Vous avez donné {{ $commande->rating->note }}/5</p>
            @endif
        </div>
    </section>
@endsection
