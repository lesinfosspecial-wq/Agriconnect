<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace producteur — Agriconnect')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@700;800&text=VA%C6%91LE&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @stack('head')
    <link rel="stylesheet" href="{{ asset('css/producteur.css') }}?v={{ filemtime(public_path('css/producteur.css')) }}">
</head>
<body>
@php
    $prenom = strtok((string) auth()->user()->nom, ' ') ?: 'Producteur';
    $marque = $prenom.' Agriculture';
    $liens = [
        ['route' => 'vendeur.dashboard', 'label' => 'Accueil', 'icon' => 'bi-house-door-fill', 'match' => 'vendeur.dashboard'],
        ['route' => 'vendeur.produits', 'label' => 'Mes produits', 'icon' => 'bi-box', 'match' => ['vendeur.produits', 'vendeur.stocks.*']],
        ['route' => 'vendeur.commandes', 'label' => 'Commandes', 'icon' => 'bi-cart', 'match' => ['vendeur.commandes', 'vendeur.commandes.show']],
        ['route' => 'vendeur.ventes', 'label' => 'Ventes', 'icon' => 'bi-bar-chart', 'match' => ['vendeur.ventes', 'vendeur.ventes.show']],
        ['route' => 'vendeur.livraisons', 'label' => 'Livraisons', 'icon' => 'bi-truck', 'match' => 'vendeur.livraisons'],
        ['route' => 'vendeur.assistant', 'label' => 'Assistant IA', 'icon' => 'bi-stars', 'match' => 'vendeur.assistant'],
        ['route' => 'vendeur.statistiques', 'label' => 'Rapport', 'icon' => 'bi-graph-up', 'match' => 'vendeur.statistiques'],
        ['route' => 'vendeur.profil', 'label' => 'Mon profil', 'icon' => 'bi-person', 'match' => 'vendeur.profil'],
        ['route' => 'vendeur.parametres', 'label' => 'Paramètres', 'icon' => 'bi-gear', 'match' => 'vendeur.parametres'],
    ];
@endphp
<div class="prod" id="prod">
    <aside class="prod-side">
        <a class="prod-brand" href="{{ route('vendeur.dashboard') }}">
            <span class="prod-logo" aria-hidden="true">
                <svg viewBox="0 0 32 32"><path d="M16 5c.4 6.2-2.2 11-7.2 13.6C12 21.2 15.2 26.5 16 28c.8-1.5 4-6.8 7.2-9.4C18.2 16 15.6 11.2 16 5Z" fill="#fff"/><path d="M16 8.2c.15 5.4-2.4 9.2-6.2 11.4" fill="none" stroke="#d9f5a8" stroke-width="1.4" stroke-linecap="round"/></svg>
            </span>
            <span>
                <strong>Agriconnect</strong>
                <small>Viens acheter</small>
            </span>
        </a>
        <nav class="prod-nav">
            @foreach ($liens as $lien)
                <a href="{{ route($lien['route']) }}" @class(['is-current' => request()->routeIs($lien['match'])])>
                    <i class="bi {{ $lien['icon'] }}"></i>
                    <span>{{ $lien['label'] }}</span>
                    @isset($lien['badge'])
                        <em>{{ $lien['badge'] }}</em>
                    @endisset
                </a>
            @endforeach
        </nav>
        <div class="prod-account">
            <img src="{{ asset('images/home/hero-farmer.jpg') }}" alt="">
            <div>
                <small>Agriculture connecté</small>
                <strong>{{ $marque }}</strong>
                <span><i></i> En ligne</span>
            </div>
        </div>
    </aside>
    <div class="prod-scrim" data-prod-close></div>
    <div class="prod-main">
        <header class="prod-top">
            <button type="button" class="prod-burger" data-prod-toggle aria-expanded="false" aria-controls="prod">
                <span class="sr-only">Ouvrir le menu</span>
                <i class="bi bi-list"></i>
            </button>
            @if (request()->routeIs('vendeur.produits'))
                <form class="prod-search" method="GET" action="{{ route('vendeur.produits') }}">
                    @if (request('filtre'))
                        <input type="hidden" name="filtre" value="{{ request('filtre') }}">
                    @endif
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit..." aria-label="Rechercher un produit">
                </form>
            @else
                <label class="prod-search">
                    <i class="bi bi-search"></i>
                    <input type="search" placeholder="Rechercher une commande, un produit..." aria-label="Rechercher une commande, un produit">
                </label>
            @endif
            <button type="button" class="prod-bell" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <i class="prod-bell-dot"></i>
            </button>
            <details class="prod-user">
                <summary>
                    <img src="{{ asset('images/home/hero-farmer.jpg') }}" alt="">
                    <span>
                        <strong>{{ $marque }}</strong>
                        <small>Producteur</small>
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </summary>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('vendeur.profil') }}">Mon profil</a>
                    <a href="{{ route('vendeur.parametres') }}">Paramètres</a>
                    <button type="submit">Déconnexion</button>
                </form>
            </details>
        </header>
        <div class="prod-canvas @yield('canvas-class')">
            @include('partials.flash')
            @yield('main')
        </div>
    </div>
</div>
<script>
    const prod = document.querySelector("#prod");
    const toggle = document.querySelector("[data-prod-toggle]");
    const close = () => {
        prod.classList.remove("is-open");
        toggle?.setAttribute("aria-expanded", "false");
    };
    toggle?.addEventListener("click", () => {
        const open = prod.classList.toggle("is-open");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    document.querySelector("[data-prod-close]")?.addEventListener("click", close);
    prod.querySelectorAll(".prod-nav a").forEach((link) => link.addEventListener("click", close));
</script>
@include('partials.geoloc')
</body>
</html>
