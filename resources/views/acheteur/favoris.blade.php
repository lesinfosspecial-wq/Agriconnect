@extends('layouts.acheteur')

@section('title', 'Mes favoris — Agriconnect')

@section('main')
    <div class="hello">
        <h1>Mes favoris</h1>
        <p>Les offres que vous avez gardées avec le cœur.</p>
    </div>
    @if ($offres->isEmpty())
        <p class="empty">Vous n’avez pas encore enregistré de favori.</p>
    @else
        <div class="offer-grid">
            @foreach ($offres as $offre)
                <article class="offer">
                    <form method="POST" action="{{ route('acheteur.favoris.toggle', $offre) }}" class="heart-form">
                        @csrf
                        <button type="submit" aria-label="Retirer des favoris"><i class="bi bi-heart-fill"></i></button>
                    </form>
                    <a href="{{ route('acheteur.offres.show', $offre) }}">
                        <figure>
                            <img src="{{ $offre->product->visuel() }}" alt="">
                        </figure>
                        <div>
                            <strong>{{ $offre->product->name }}</strong>
                            <small>{{ $offre->quartier }} @if($offre->distance_km) · {{ km($offre->distance_km) }} @endif</small>
                            <span class="price">{{ fcfa($offre->seller_price) }} / {{ $offre->unit }}</span>
                            <span class="cta">Voir l'offre</span>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>
    @endif
@endsection
