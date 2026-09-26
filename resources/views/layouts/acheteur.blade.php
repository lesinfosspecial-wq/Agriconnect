<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Agriconnect')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@700;800&text=VA%C6%91LE&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/acheteur.css') }}?v={{ filemtime(public_path('css/acheteur.css')) }}">
</head>
<body>
@php
    $user = auth()->user();
    $prenom = strtok((string) $user->nom, ' ') ?: 'Acheteur';
    $liens = [
        ['route' => 'acheteur.dashboard', 'label' => 'Accueil', 'icon' => 'bi-house-door-fill', 'match' => 'acheteur.dashboard'],
        ['url' => route('acheteur.explorer'), 'label' => 'Explorer', 'icon' => 'bi-compass', 'match' => 'acheteur.explorer'],
        ['route' => 'acheteur.reservations.index', 'label' => 'Mes commandes', 'icon' => 'bi-bag', 'match' => 'acheteur.reservations.*'],
        ['route' => 'acheteur.favoris', 'label' => 'Mes favoris', 'icon' => 'bi-heart', 'match' => 'acheteur.favoris'],
        ['route' => 'acheteur.notifications', 'label' => 'Notifications', 'icon' => 'bi-bell', 'match' => 'acheteur.notifications', 'badge' => \App\Models\MarketNotification::where('user_id', $user->id)->where('lu', false)->count()],
        ['route' => 'acheteur.profil', 'label' => 'Profil', 'icon' => 'bi-person', 'match' => 'acheteur.profil'],
        ['route' => 'acheteur.parametres', 'label' => 'Paramètres', 'icon' => 'bi-gear', 'match' => 'acheteur.parametres'],
    ];
@endphp
<div class="buy" id="buy">
    <aside class="buy-side">
        <a class="buy-brand" href="{{ route('acheteur.dashboard') }}">
            <span><svg viewBox="0 0 32 32"><path d="M16 5c.4 6.2-2.2 11-7.2 13.6C12 21.2 15.2 26.5 16 28c.8-1.5 4-6.8 7.2-9.4C18.2 16 15.6 11.2 16 5Z" fill="#fff"/></svg></span>
            <strong>Agriconnect</strong>
            <small>Viens acheter</small>
        </a>
        <nav>
            @foreach ($liens as $lien)
                <a href="{{ $lien['url'] ?? route($lien['route']) }}" @class(['is-on' => ($lien['match'] ?? '') !== '' && request()->routeIs($lien['match'])])>
                    <i class="bi {{ $lien['icon'] }}"></i> {{ $lien['label'] }}
                    @if (! empty($lien['badge']))<em>{{ $lien['badge'] }}</em>@endif
                </a>
            @endforeach
        </nav>
        <div class="buy-me">
            <b>{{ mb_strtoupper(mb_substr($prenom, 0, 1)) }}</b>
            <div>
                <strong>{{ $user->nom }}</strong>
                <small>Acheteur</small>
            </div>
        </div>
    </aside>
    <div class="buy-scrim" data-buy-close></div>
    <div class="buy-main">
        <header class="buy-top">
            <button type="button" class="buy-burger" data-buy-toggle aria-label="Ouvrir le menu"><i class="bi bi-list"></i></button>
            <form class="buy-search" method="GET" action="{{ route('acheteur.dashboard') }}">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit, un vendeur, une localisation...">
            </form>
            <span class="buy-place"><i class="bi bi-geo-alt-fill"></i> {{ $user->ville ?: $user->quartier }}</span>
            <a class="buy-bell" href="{{ route('acheteur.notifications') }}" aria-label="Notifications"><i class="bi bi-bell"></i></a>
            <details class="buy-user">
                <summary><b>{{ mb_strtoupper(mb_substr($prenom, 0, 1)) }}</b></summary>
                <div>
                    <a href="{{ route('acheteur.profil') }}">Profil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
                </div>
            </details>
        </header>
        <div class="buy-canvas">
            @include('partials.flash')
            @yield('main')
        </div>
        <nav class="buy-dock">
            <a href="{{ route('acheteur.dashboard') }}" @class(['is-on' => request()->routeIs('acheteur.dashboard')])><i class="bi bi-house"></i>Accueil</a>
            <a href="{{ route('acheteur.reservations.index') }}" @class(['is-on' => request()->routeIs('acheteur.reservations.*')])><i class="bi bi-bag"></i>Commandes</a>
            <a href="{{ route('acheteur.profil') }}" @class(['is-on' => request()->routeIs('acheteur.profil')])><i class="bi bi-person"></i>Profil</a>
        </nav>
    </div>
</div>
<script>
    const buy = document.querySelector("#buy");
    const toggle = document.querySelector("[data-buy-toggle]");
    const fermer = () => buy.classList.remove("is-open");
    toggle?.addEventListener("click", () => buy.classList.toggle("is-open"));
    document.querySelector("[data-buy-close]")?.addEventListener("click", fermer);
    buy.querySelectorAll(".buy-side a").forEach((a) => a.addEventListener("click", fermer));
</script>
@include('partials.geoloc')
</body>
</html>
