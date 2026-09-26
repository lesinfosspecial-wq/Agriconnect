@extends('layouts.base')

@section('body-class', 'page-home app-space')

@section('content')
    @php
        $role = auth()->user()->role;
        $liens = match ($role) {
            'admin' => [
                ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'match' => 'admin.dashboard'],
                ['route' => 'admin.annonces.index', 'label' => 'Stocks', 'match' => 'admin.annonces.*'],
                ['route' => 'admin.identites', 'label' => 'Utilisateurs', 'match' => 'admin.identites*'],
                ['route' => 'admin.transactions', 'label' => 'Ventes', 'match' => 'admin.transactions*'],
            ],
            'vendeur' => [
                ['route' => 'vendeur.dashboard', 'label' => 'Tableau de bord', 'match' => 'vendeur.dashboard'],
                ['route' => 'vendeur.stocks.create', 'label' => 'Déclarer un stock', 'match' => 'vendeur.stocks.create'],
                ['url' => route('vendeur.dashboard').'#commandes', 'label' => 'Collectes', 'match' => ''],
            ],
            default => [
                ['route' => 'acheteur.dashboard', 'label' => 'Offres proches', 'match' => ['acheteur.dashboard', 'acheteur.offres.*']],
                ['route' => 'acheteur.reservations.index', 'label' => 'Réservations', 'match' => 'acheteur.reservations.*'],
            ],
        };
    @endphp
    <div class="dash" id="dash">
        <aside class="dash-side">
            <a href="{{ auth()->user()->espaceRoute() }}" class="hbrand">
                <span class="hbrand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32"><path d="M8 20.5c4-.6 7.2-5.2 8.2-11.2 4.2 2.6 8.2 3.6 10.3 3.4-1.2 7.2-6.4 12.4-12.4 13.2-3.2-1-6.1-2.6-6.1-5.4Z" fill="#fff"/><path d="M16.2 9.5c.2 4.2-1.6 8.2-4.6 10.4" fill="none" stroke="#d9f5a8" stroke-width="1.4" stroke-linecap="round"/></svg>
                </span>
                <span>
                    <strong>Agriconnect</strong>
                    <small>{{ \App\Support\Libelles::role($role) }}</small>
                </span>
            </a>
            <nav class="dash-nav">
                @foreach ($liens as $lien)
                    @php
                        $href = $lien['url'] ?? route($lien['route']);
                        $actif = ($lien['match'] ?? '') !== '' && request()->routeIs($lien['match']);
                    @endphp
                    <a href="{{ $href }}" @class(['is-current' => $actif])>{{ $lien['label'] }}</a>
                @endforeach
            </nav>
            <div class="dash-user">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->quartier }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="hbtn hbtn-line">Déconnexion</button>
                </form>
            </div>
        </aside>
        <div class="dash-main">
            <div class="dash-top">
                <button type="button" class="hnav-burger" data-dash-toggle aria-expanded="false" aria-controls="dash">
                    <span class="sr-only">Ouvrir le menu</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </button>
                <p class="hkicker">@yield('eyebrow')</p>
            </div>
            <header class="app-head">
                <div>
                    <h1>@yield('heading')</h1>
                    @hasSection('lead')
                        <p class="app-lead">@yield('lead')</p>
                    @endif
                </div>
                <div class="workspace-actions">@yield('actions')</div>
            </header>
            @include('partials.flash')
            @yield('main')
        </div>
    </div>
@endsection
