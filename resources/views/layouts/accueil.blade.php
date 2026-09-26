@extends('layouts.base')

@section('body-class', 'page-home')

@section('content')
    <div class="home-wrap">
        <header class="hnav">
            <a href="{{ route('home') }}" class="hbrand">
                <span class="hbrand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32"><path d="M8 20.5c4-.6 7.2-5.2 8.2-11.2 4.2 2.6 8.2 3.6 10.3 3.4-1.2 7.2-6.4 12.4-12.4 13.2-3.2-1-6.1-2.6-6.1-5.4Z" fill="#fff"/><path d="M16.2 9.5c.2 4.2-1.6 8.2-4.6 10.4" fill="none" stroke="#d9f5a8" stroke-width="1.4" stroke-linecap="round"/></svg>
                </span>
                <span>
                    <strong>Agriconnect</strong>
                    <small>Viens acheter</small>
                </span>
            </a>
            <button type="button" class="hnav-burger" data-nav-toggle aria-expanded="false" aria-controls="hnav-menu">
                <span class="sr-only">Ouvrir le menu</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
            <div class="hnav-menu" id="hnav-menu">
                <nav class="hnav-links">
                    <a href="{{ route('home') }}" @class(['is-current' => request()->routeIs('home') && request('q') === null])>Accueil</a>
                    <a href="{{ route('home') }}#produits">Produits</a>
                    <a href="{{ route('home') }}#vendeurs">Vendeurs</a>
                    <a href="{{ route('home') }}#acheteurs">Acheteurs</a>
                    <a href="{{ route('home') }}#propos">À propos</a>
                </nav>
                <div class="hnav-actions">
                    <button type="button" class="htheme" data-theme-toggle aria-label="Changer le thème">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M4.8 4.8l1.6 1.6M17.6 17.6l1.6 1.6M19.2 4.8l-1.6 1.6M6.4 17.6l-1.6 1.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                    @auth
                        <a class="hbtn hbtn-solid" href="{{ auth()->user()->espaceRoute() }}">Mon espace</a>
                    @else
                        <a @class(['hbtn hbtn-line', 'is-on' => request()->routeIs('login')]) href="{{ route('login') }}">Se connecter</a>
                        <a @class(['hbtn hbtn-solid', 'is-on' => request()->routeIs('register')]) href="{{ route('register') }}">S’inscrire</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            @yield('main')
        </main>

        <footer class="hfooter">
            <div>
                <strong>Agriconnect</strong>
                <p>Viens acheter. Plateforme de vente des denrées périssables.</p>
            </div>
            <p>Lomé · ESIG Tech Arena 2026</p>
        </footer>
    </div>
@endsection
