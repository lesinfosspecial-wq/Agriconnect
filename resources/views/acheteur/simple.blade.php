@extends('layouts.acheteur')

@section('title', $titre.' — Agriconnect')

@section('main')
    <div class="hello">
        <h1>{{ $titre }}</h1>
        @if ($texte)<p>{{ $texte }}</p>@endif
    </div>
    @isset($notifications)
        <div class="alertes">
            @foreach ($notifications as $alerte)
                <article @class(['alerte', 'is-new' => ! $alerte->lu])>
                    <strong>
                        {{ $alerte->type === 'changement_prix' ? 'Changement de prix' : 'Nouvelle offre' }}
                    </strong>
                    <p>{{ $alerte->message }}</p>
                    <small>{{ $alerte->created_at?->translatedFormat('d F Y \à H\hi') }}</small>
                    @if ($alerte->stock && in_array($alerte->stock->statut, ['publie', 'partiellement_reserve'], true))
                        <a href="{{ route('acheteur.offres.show', $alerte->stock) }}">Voir l'offre</a>
                    @endif
                </article>
            @endforeach
        </div>
    @endisset
    @isset($lignes)
        <div class="order-list">
            @foreach ($lignes as $ligne)
                <article class="order"><p>{{ $ligne }}</p></article>
            @endforeach
        </div>
    @endisset
@endsection
