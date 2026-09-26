@extends('layouts.admin')

@section('title', $stock->product->name.' — Agriconnect')
@section('heading', $stock->product->name)
@section('lead', $stock->seller->name.' · '.$stock->quartier)

@section('main')
    @php
        $pertinence = $stock->pertinence();
        $clos = in_array($stock->status, ['vendu', 'expire', 'annule'], true);
    @endphp
    <a class="back" href="{{ route('admin.annonces.index') }}">Retour aux stocks</a>
    <section class="board board-split">
        <article class="price-hero">
            <img src="{{ $stock->photo_path ? asset('storage/'.$stock->photo_path) : $stock->product->visuel() }}" alt="">
            <div>
                <p>Prix vendeur</p>
                <strong>{{ fcfa($stock->seller_price) }}</strong>
                <small>Estimation {{ fcfa($stock->ai_price) }} · {{ fcfa($stock->ai_min) }} – {{ fcfa($stock->ai_max) }}</small>
                @if ($pertinence)<span class="pill pill-ok">{{ $pertinence['label'] }}</span>@endif
            </div>
        </article>
        <article class="panel-card facts">
            <header>
                <h2>Fiche</h2>
                <span class="pill {{ $stock->status === 'suspendu' ? 'pill-bad' : 'pill-ok' }}">{{ \App\Support\Libelles::statutStock($stock->status) }}</span>
            </header>
            <ul>
                <li><span>Quantité</span><b>{{ number_format($stock->availableQuantity(), 0, ',', ' ') }} / {{ number_format($stock->quantity, 0, ',', ' ') }} {{ $stock->unit }}</b></li>
                <li><span>Fraîcheur</span><b>{{ \App\Support\Libelles::fraicheur($stock->freshness) }}</b></li>
                <li><span>Urgence</span><b>{{ \App\Support\Libelles::urgence($stock->urgency) }}</b></li>
                <li><span>Limite</span><b>{{ $stock->expires_on->translatedFormat('d F Y') }}</b></li>
                <li><span>Collecte</span><b>{{ \App\Support\Libelles::collecte($stock->pickup_mode) }}</b></li>
                <li><span>Vendeur</span><b><a href="{{ route('admin.identites.show', $stock->seller) }}">{{ $stock->seller->name }}</a> · {{ $stock->seller->phone }}</b></li>
                <li><span>Badge</span><b>{{ \App\Support\Libelles::verification($stock->seller->verification) }}</b></li>
            </ul>
            @unless ($clos)
                <form method="POST" action="{{ route('admin.annonces.suspendre', $stock) }}">
                    @csrf
                    <button class="btn-quiet {{ $stock->status === 'suspendu' ? 'is-ok' : 'is-stop' }}" type="submit">
                        {{ $stock->status === 'suspendu' ? 'Réactiver le stock' : 'Suspendre le stock' }}
                    </button>
                </form>
            @endunless
        </article>
    </section>
@endsection
