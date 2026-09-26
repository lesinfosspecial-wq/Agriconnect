@extends('layouts.admin')

@section('title', $personne->nom.' — Agriconnect')
@section('heading', $personne->nom)
@section('lead', \App\Support\Libelles::role($personne->role).' · '.$personne->quartier)

@section('main')
    <a class="back" href="{{ route('admin.identites') }}">Retour aux utilisateurs</a>
    <section class="board board-split">
        <article class="panel-card facts">
            <header>
                <h2>Profil</h2>
                <span class="pill {{ $personne->estVerifie() ? 'pill-ok' : 'pill-wait' }}">{{ \App\Support\Libelles::verification($personne->verification) }}</span>
            </header>
            <ul>
                @if ($personne->photo_profil)
                    <li><span>Photo</span><b><img src="{{ asset('storage/'.$personne->photo_profil) }}" alt="Photo de {{ $personne->nom }}" style="width:72px;height:72px;object-fit:cover;border-radius:12px"></b></li>
                @endif
                <li><span>Téléphone</span><b>{{ $personne->telephone }}</b></li>
                <li><span>Ville</span><b>{{ $personne->ville }}</b></li>
                <li><span>Quartier</span><b>{{ $personne->quartier }}</b></li>
                @if ($personne->isAcheteur())
                    <li><span>Rayon</span><b>{{ $personne->rayon_km ? km($personne->rayon_km) : '25 km' }}</b></li>
                @endif
                @if ($personne->noteMoyenne())
                    <li><span>Note</span><b>{{ $personne->noteMoyenne() }}/5</b></li>
                @endif
                @if ($personne->isVendeur())
                    <li>
                        <span>Preuve d'activité</span>
                        <b>
                            @if ($personne->piece_agriculteur)
                                <a href="{{ asset('storage/'.$personne->piece_agriculteur) }}" target="_blank" rel="noopener">Ouvrir le document</a>
                            @else
                                Aucun document envoyé
                            @endif
                        </b>
                    </li>
                @endif
            </ul>
            <form method="POST" action="{{ route('admin.utilisateurs.verifier', $personne) }}">
                @csrf
                <button class="btn-quiet {{ $personne->estVerifie() ? 'is-stop' : 'is-ok' }}" type="submit">
                    {{ $personne->estVerifie() ? 'Retirer le badge' : 'Délivrer le badge' }}
                </button>
            </form>
        </article>
        <article class="panel-card">
            <header><h2>{{ $personne->isVendeur() ? 'Annonces' : 'Réservations' }}</h2></header>
            <ul class="rows">
                @if ($personne->isVendeur())
                    @forelse ($personne->stocks as $stock)
                        <li>
                            <a href="{{ route('admin.annonces.show', $stock) }}">
                                <strong>{{ $stock->product->name }}</strong>
                                <small>{{ $stock->quartier }} · {{ fcfa($stock->seller_price) }} · {{ \App\Support\Libelles::statutStock($stock->status) }}</small>
                            </a>
                        </li>
                    @empty
                        <li><span class="muted">Aucun stock.</span></li>
                    @endforelse
                @else
                    @forelse ($personne->orders as $order)
                        <li>
                            <a href="{{ route('admin.transactions.show', $order) }}">
                                <strong>{{ $order->stock->product->name }}</strong>
                                <small>{{ number_format($order->quantity, 1, ',', ' ') }} {{ $order->stock->unit }} · {{ \App\Support\Libelles::commande($order->status) }}</small>
                            </a>
                        </li>
                    @empty
                        <li><span class="muted">Aucune réservation.</span></li>
                    @endforelse
                @endif
            </ul>
        </article>
    </section>
@endsection
