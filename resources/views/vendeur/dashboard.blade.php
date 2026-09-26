@extends('layouts.producteur')

@section('title', 'Accueil — Agriconnect')

@section('main')
    @php
        $prenom = strtok((string) auth()->user()->nom, ' ') ?: 'Kossi';
        $tomate = $stocks->first(fn ($stock) => str_contains(mb_strtolower($stock->product->nom), 'tomate'));
    @endphp

    @unless (auth()->user()->estVerifie())
        <section class="prod-notice">
            <p>Votre identité est <strong>{{ \App\Support\Libelles::verification(auth()->user()->verification) }}</strong>. Vous pouvez quand même publier un stock.</p>
            @if (auth()->user()->verification !== 'en_cours')
                <form method="POST" action="{{ route('vendeur.verification') }}">
                    @csrf
                    <button type="submit">Demander la vérification</button>
                </form>
            @endif
        </section>
    @endunless

    <section class="hero-row">
        <article class="hero-card">
            <img src="{{ asset('images/home/hero-farmer.jpg') }}" alt="Producteur dans son champ">
            <div class="hero-copy">
                <h1>Bonjour {{ $prenom }}</h1>
                <p>Voici l'état de votre activité aujourd'hui.</p>
            <span class="since">Inscrit le {{ auth()->user()->created_at?->translatedFormat('d F Y') }}</span>
            </div>
        </article>
        <article class="weather-card">
            @if ($meteo)
                <span class="sun" aria-hidden="true"><i class="bi {{ $meteo['icone'] }}"></i></span>
                <small>{{ $meteo['lieu'] }}</small>
                <strong>{{ $meteo['temperature'] }}°C</strong>
                <span>{{ $meteo['texte'] }}</span>
            @else
                <span class="sun" aria-hidden="true"><i class="bi bi-cloud"></i></span>
                <small>{{ auth()->user()->quartier }}</small>
                <strong>—</strong>
                <span>Météo indisponible</span>
            @endif
        </article>
    </section>

    @php
        $entiers = fn ($nombre) => fmod((float) $nombre, 1.0) === 0.0
            ? number_format((float) $nombre, 0, ',', ' ')
            : number_format((float) $nombre, 1, ',', ' ');
        $disponible = fn ($stock) => in_array($stock->statut, ['publie', 'partiellement_reserve'], true) && $stock->availableQuantity() > 0 && $stock->statut === 'publie';
        $reserve = fn ($stock) => in_array($stock->statut, ['partiellement_reserve', 'reserve'], true);
        $nouvelles = $commandes->where('statut', \App\Models\Order::RESERVEE)->count();
        $preparation = $commandes->where('statut', \App\Models\Order::EN_PREPARATION)->count();
        $retrait = $commandes->where('statut', \App\Models\Order::EN_COLLECTE)->count();
        $ouvertes = $commandes->whereNotIn('statut', [\App\Models\Order::TERMINEE, \App\Models\Order::ANNULEE]);
        $aEcouler = $stocks->filter(fn ($stock) => $stock->joursRestants() <= 3 && ! in_array($stock->statut, ['annule', 'expire', 'vendu'], true));
        $priorite = $aEcouler->sortBy(fn ($stock) => $stock->joursRestants())->first()
            ?? $stocks->sortBy(fn ($stock) => $stock->joursRestants())->first();
        $confiance = auth()->user()->niveauConfiance();
    @endphp

    <section class="kpi-row">
        <article>
            <span class="kpi-ico ico-green"><i class="bi bi-basket"></i></span>
            <div>
                <p>Mes produits</p>
                <strong>{{ $stocks->count() }}</strong>
                <small>{{ $stocks->filter($disponible)->count() }} disponibles · {{ $stocks->filter($reserve)->count() }} réservés</small>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-blue"><i class="bi bi-cart"></i></span>
            <div>
                <p>Commandes</p>
                <strong>{{ $ouvertes->count() }}</strong>
                <small>{{ $nouvelles }} nouvelles · {{ $preparation }} en préparation · {{ $retrait }} à retirer</small>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-green"><i class="bi bi-wallet2"></i></span>
            <div>
                <p>Revenus</p>
                <strong>{{ fcfa($revenu) }}</strong>
                <small>{{ $ventes }} vente{{ $ventes > 1 ? 's' : '' }} encaissée{{ $ventes > 1 ? 's' : '' }}</small>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-green"><i class="bi bi-shield-check"></i></span>
            <div>
                <p>Niveau de confiance</p>
                <strong>{{ $confiance['score'] }} <span>/ 100</span></strong>
                <small class="good">{{ $confiance['libelle'] }}</small>
                <small>{{ $confiance['detail'] }}</small>
                <i class="score-bar" style="--score: {{ $confiance['score'] }}%"></i>
            </div>
        </article>
    </section>

    <section class="board">
        <article class="panel-card">
            <header>
                <h2><i class="bi bi-bell"></i> Actions à faire</h2>
                <a href="{{ route('vendeur.commandes') }}">Voir tout</a>
            </header>
            <ul class="todo">
                @if ($nouvelles > 0)
                    <li><i class="dot dot-orange"></i><span>{{ $nouvelles }} commande{{ $nouvelles > 1 ? 's' : '' }} à confirmer</span></li>
                @endif
                @if ($preparation > 0)
                    <li><i class="dot dot-blue"></i><span>{{ $preparation }} commande{{ $preparation > 1 ? 's' : '' }} en préparation</span></li>
                @endif
                @if ($retrait > 0)
                    <li><i class="dot dot-green"></i><span>{{ $retrait }} retrait{{ $retrait > 1 ? 's' : '' }} à confirmer</span></li>
                @endif
                @if ($aEcouler->isNotEmpty())
                    <li><i class="dot dot-orange"></i><span>{{ $aEcouler->count() }} stock{{ $aEcouler->count() > 1 ? 's' : '' }} arrivent à la limite</span></li>
                @endif
                @if ($nouvelles + $preparation + $retrait + $aEcouler->count() === 0)
                    <li><i class="dot dot-green"></i><span>Rien d’urgent pour le moment.</span></li>
                @endif
            </ul>
        </article>

        <article class="panel-card">
            <header>
                <h2>Mes produits</h2>
                <a href="{{ route('vendeur.produits') }}">Voir tout</a>
            </header>
            <ul class="rows">
                @forelse ($stocks->take(3) as $stock)
                    <li>
                        <img src="{{ $stock->photo_path ? asset('storage/'.$stock->photo_path) : $stock->product->visuel() }}" alt="">
                        <div>
                            <strong><a href="{{ route('vendeur.stocks.show', $stock) }}">{{ $stock->product->name }}</a></strong>
                            <small>{{ $entiers($stock->availableQuantity()) }} {{ $stock->unit }}</small>
                        </div>
                        <span class="muted">{{ number_format($stock->seller_price, 0, ',', ' ') }} FCFA/{{ $stock->unit }}</span>
                        @if ($reserve($stock))
                            <em class="pill pill-wait">Réservé</em>
                        @elseif ($stock->availableQuantity() <= 0 || in_array($stock->statut, ['vendu', 'expire', 'annule'], true))
                            <em class="pill pill-done">Épuisé</em>
                        @else
                            <em class="pill pill-ok">Disponible</em>
                        @endif
                    </li>
                @empty
                    <li><span>Aucun produit publié.</span></li>
                @endforelse
            </ul>
        </article>

        <article class="panel-card">
            <header>
                <h2>Commandes récentes</h2>
                <a href="{{ route('vendeur.commandes') }}">Voir tout</a>
            </header>
            <ul class="rows orders">
                @forelse ($commandes->take(3) as $commande)
                    @php
                        $pilule = match ($commande->statut) {
                            \App\Models\Order::EN_COLLECTE => 'pill-ship',
                            \App\Models\Order::TERMINEE, \App\Models\Order::COLLECTEE => 'pill-done',
                            \App\Models\Order::EN_PREPARATION => 'pill-wait',
                            default => 'pill-ok',
                        };
                    @endphp
                    <li>
                        <img src="{{ $commande->stock->product->visuel() }}" alt="">
                        <div>
                            <strong class="ref"><a href="{{ route('vendeur.commandes.show', $commande) }}">#{{ $commande->id }}</a> <small>{{ $commande->created_at?->format('d/m/Y') }}</small></strong>
                            <small>{{ $commande->stock->product->name }} · {{ $entiers($commande->quantite) }} {{ $commande->stock->unit }}</small>
                        </div>
                        <em class="pill {{ $pilule }}">{{ \App\Support\Libelles::commande($commande->statut) }}</em>
                    </li>
                @empty
                    <li><span>Aucune commande pour le moment.</span></li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="promo-row">
        <article class="market-card">
            <h2>À écouler en premier</h2>
            @if ($priorite)
                <p>{{ $priorite->product->name }} à {{ $priorite->quartier }} · {{ $priorite->joursRestants() }} jour{{ $priorite->joursRestants() > 1 ? 's' : '' }} avant la limite.</p>
                <a href="{{ route('vendeur.stocks.show', $priorite) }}">Voir le stock <i class="bi bi-arrow-right"></i></a>
            @else
                <p>Publiez un stock pour voir ici ce qu’il faut vendre en premier.</p>
                <a href="{{ route('vendeur.stocks.create') }}">Ajouter un produit <i class="bi bi-arrow-right"></i></a>
            @endif
        </article>
        <article class="ai-card">
            <div>
                <h2><i class="bi bi-stars"></i> Assistant IA</h2>
                @if ($tomate && $tomate->ai_price)
                    <p>{{ $tomate->product->name }} : votre prix est {{ number_format($tomate->seller_price, 0, ',', ' ') }} FCFA, la fourchette va jusqu’à {{ number_format($tomate->ai_max, 0, ',', ' ') }}.</p>
                    <a href="{{ route('vendeur.stocks.show', $tomate) }}">Voir l'analyse</a>
                @else
                    <p>Décrivez un stock en français ou en éwé. Rien n’est publié sans votre accord.</p>
                    <a href="{{ route('vendeur.assistant') }}">Ouvrir l’assistant</a>
                @endif
            </div>
            <img src="{{ $tomate ? $tomate->product->visuel() : asset('images/home/produit-tomates.jpg') }}" alt="">
        </article>
    </section>
@endsection
