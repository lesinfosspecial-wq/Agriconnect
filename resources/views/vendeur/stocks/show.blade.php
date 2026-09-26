@extends('layouts.producteur')

@section('title', $stock->product->name.' — Agriconnect')

@section('main')
    @php
        $entiers = fn ($nombre) => fmod((float) $nombre, 1.0) === 0.0
            ? number_format((float) $nombre, 0, ',', ' ')
            : number_format((float) $nombre, 1, ',', ' ');
        $pertinence = $stock->ai_price ? $stock->pertinence() : null;
        $vision = $stock->vision;
        $photo = $stock->photo_path ? asset('storage/'.$stock->photo_path) : $stock->product->visuel();
    @endphp

    <a class="fiche-back" href="{{ route('vendeur.produits') }}">Retour aux produits</a>
    <header class="fiche-head">
        <div>
            <h1>{{ $stock->product->name }}</h1>
            <p>{{ \App\Support\Libelles::statutStock($stock->statut) }} · {{ $stock->quartier }}</p>
        </div>
        <a class="fiche-edit" href="{{ route('vendeur.stocks.edit', $stock) }}">Modifier le produit</a>
    </header>

    <section class="fiche">
        <article class="fiche-prix">
            @if ($stock->ai_price)
                <p>Régression linéaire · {{ $stock->dernierePrediction?->facteurs['echantillons'] ?? '—' }} observations</p>
                <h2>{{ fcfa($stock->ai_price) }}</h2>
                <span>Fourchette {{ fcfa($stock->ai_min) }} – {{ fcfa($stock->ai_max) }} / {{ $stock->unit }}</span>
                @if ($pertinence)
                    <em class="pill {{ $pertinence['tone'] === 'bad' ? 'pill-wait' : 'pill-ok' }}">{{ $pertinence['label'] }}</em>
                @endif
                <strong>Votre prix : {{ fcfa($stock->seller_price) }} / {{ $stock->unit }}</strong>
            @else
                <p>Prix publié</p>
                <h2>{{ fcfa($stock->seller_price) }}</h2>
                <span>La limite est le {{ $stock->expires_on->translatedFormat('d F Y') }}. L’analyse photo et prix se lance quand vous modifiez le produit.</span>
            @endif
            @if ($vision)
                <span>Pourriture estimée {{ $vision['putrefaction'] }} % · fraîcheur {{ $vision['fraicheur'] }}/5. {{ $vision['resume'] }}</span>
            @endif
            @if ($stock->factors)
                <ul>
                    @foreach ($stock->factors as $facteur)
                        <li>
                            <span>{{ $facteur['label'] }}</span>
                            <b>{{ $facteur['impact'] >= 0 ? '+' : '−' }}{{ number_format(abs($facteur['impact']), 0, ',', ' ') }} FCFA</b>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="fiche-photo">
            <img src="{{ $photo }}" alt="{{ $stock->product->name }}">
            <dl>
                <div><dt>Quantité</dt><dd>{{ $entiers($stock->quantity) }} {{ $stock->unit }}</dd></div>
                <div><dt>Encore libre</dt><dd>{{ $entiers($stock->availableQuantity()) }} {{ $stock->unit }}</dd></div>
                <div><dt>Lieu</dt><dd>{{ $stock->quartier }}</dd></div>
                <div><dt>Récolte</dt><dd>{{ $stock->harvested_on->translatedFormat('d F Y') }}</dd></div>
                <div><dt>Limite</dt><dd>{{ $stock->expires_on->translatedFormat('d F Y') }} · {{ $stock->joursRestants() }} j</dd></div>
                <div><dt>Fraîcheur</dt><dd>{{ \App\Support\Libelles::fraicheur($stock->freshness) }}</dd></div>
                <div><dt>Urgence</dt><dd>{{ \App\Support\Libelles::urgence($stock->urgency) }}</dd></div>
                <div><dt>Retrait</dt><dd>{{ \App\Support\Libelles::collecte($stock->pickup_mode) }}</dd></div>
            </dl>
        </article>
    </section>

@endsection
