@extends('layouts.base')

@section('content')
    <header class="topbar">
        <a href="{{ route('home') }}" class="brand">@include('partials.logo')</a>
        <nav class="top-links">
            <a href="{{ route('home') }}#parcours">Parcours</a>
            <a href="{{ route('home') }}#roles">Espaces</a>
            @auth
                <a class="btn btn-small" href="{{ auth()->user()->espaceRoute() }}">Mon espace</a>
            @else
                <a href="{{ route('login') }}">Connexion</a>
                <a class="btn btn-small" href="{{ route('register') }}">Inscription</a>
            @endauth
        </nav>
    </header>
    <main>
        @yield('main')
    </main>
    <footer class="site-footer">
        <div>
            @include('partials.logo')
            <p>Plateforme de vente d’urgence des denrées périssables. ESIG Tech Arena 2026.</p>
        </div>
        <p>Lomé · prix en FCFA · validation humaine avant publication</p>
    </footer>
@endsection
