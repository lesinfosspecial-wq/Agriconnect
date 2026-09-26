<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration — Agriconnect')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@700;800&text=VA%C6%91LE&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
@php
    $prenom = strtok((string) auth()->user()->nom, ' ') ?: 'Admin';
    $initiale = mb_strtoupper(mb_substr($prenom, 0, 1));
    $liens = [
        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'bi-house-door-fill', 'match' => 'admin.dashboard'],
        ['route' => 'admin.annonces.index', 'label' => 'Stocks', 'icon' => 'bi-box', 'match' => 'admin.annonces.*'],
        ['route' => 'admin.identites', 'label' => 'Utilisateurs', 'icon' => 'bi-people', 'match' => 'admin.identites*'],
        ['route' => 'admin.transactions', 'label' => 'Ventes', 'icon' => 'bi-receipt', 'match' => 'admin.transactions*'],
    ];
    $recherche = match (true) {
        request()->routeIs('admin.identites*') => [route('admin.identites'), 'Nom, téléphone ou quartier'],
        request()->routeIs('admin.transactions*') => [route('admin.transactions'), 'Acheteur, vendeur ou produit'],
        default => [route('admin.annonces.index'), 'Produit, vendeur ou quartier'],
    };
@endphp
<div class="adm" id="adm">
    <aside class="adm-side">
        <a class="adm-brand" href="{{ route('admin.dashboard') }}">
            <span class="adm-logo" aria-hidden="true">
                <svg viewBox="0 0 32 32"><path d="M16 5c.4 6.2-2.2 11-7.2 13.6C12 21.2 15.2 26.5 16 28c.8-1.5 4-6.8 7.2-9.4C18.2 16 15.6 11.2 16 5Z" fill="#fff"/><path d="M16 8.2c.15 5.4-2.4 9.2-6.2 11.4" fill="none" stroke="#d9f5a8" stroke-width="1.4" stroke-linecap="round"/></svg>
            </span>
            <span>
                <strong>Agriconnect</strong>
                <small>Viens acheter</small>
            </span>
        </a>
        <nav class="adm-nav">
            @foreach ($liens as $lien)
                <a href="{{ route($lien['route']) }}" @class(['is-current' => request()->routeIs($lien['match'])])>
                    <i class="bi {{ $lien['icon'] }}"></i>
                    <span>{{ $lien['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="adm-account">
            <span class="adm-avatar">{{ $initiale }}</span>
            <div>
                <small>Administration</small>
                <strong>{{ auth()->user()->nom }}</strong>
                <span><i></i> En ligne</span>
            </div>
        </div>
    </aside>
    <div class="adm-scrim" data-adm-close></div>
    <div class="adm-main">
        <header class="adm-top">
            <button type="button" class="adm-burger" data-adm-toggle aria-expanded="false" aria-controls="adm">
                <span class="sr-only">Ouvrir le menu</span>
                <i class="bi bi-list"></i>
            </button>
            <form class="adm-search" method="GET" action="{{ $recherche[0] }}">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $recherche[1] }}" aria-label="{{ $recherche[1] }}">
            </form>
            <a class="adm-bell" href="{{ route('admin.identites') }}" aria-label="Comptes à vérifier">
                <i class="bi bi-bell"></i>
                <i class="adm-bell-dot"></i>
            </a>
            <details class="adm-user">
                <summary>
                    <span class="adm-avatar">{{ $initiale }}</span>
                    <span>
                        <strong>{{ auth()->user()->nom }}</strong>
                        <small>Administrateur</small>
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </summary>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Déconnexion</button>
                </form>
            </details>
        </header>
        <div class="adm-canvas">
            @if (! request()->routeIs('admin.dashboard'))
                <div class="adm-head">
                    <div>
                        <h1>@yield('heading')</h1>
                        @hasSection('lead')<p>@yield('lead')</p>@endif
                    </div>
                    @yield('actions')
                </div>
            @endif
            @include('partials.flash')
            @yield('main')
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const adm = document.querySelector("#adm");
    const toggle = document.querySelector("[data-adm-toggle]");
    const fermer = () => {
        adm.classList.remove("is-open");
        toggle?.setAttribute("aria-expanded", "false");
    };
    toggle?.addEventListener("click", () => {
        const open = adm.classList.toggle("is-open");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    document.querySelector("[data-adm-close]")?.addEventListener("click", fermer);
    adm.querySelectorAll(".adm-nav a").forEach((lien) => lien.addEventListener("click", fermer));
</script>
@stack('scripts')
</body>
</html>
