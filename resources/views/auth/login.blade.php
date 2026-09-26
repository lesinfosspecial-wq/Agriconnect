@extends('layouts.accueil')

@section('title', 'Connexion — Agriconnect')

@section('main')
    <section class="auth-screen">
        <aside class="auth-visual is-portrait">
            <img src="{{ asset('images/home/hero-farmer.jpg') }}" alt="">
            <div class="auth-visual-shade"></div>
            <div class="auth-visual-copy">
                <p class="hpill">Bon retour sur le marché</p>
                <h1>Reprendre là où <em>le stock</em> s’est arrêté.</h1>
                <ul class="auth-points">
                    <li>Producteur : vos stocks et vos collectes</li>
                    <li>Acheteur : les offres proches et vos réservations</li>
                    <li>Admin : les badges d’identité</li>
                </ul>
            </div>
        </aside>
        <div class="auth-panel">
            <form class="auth-card" method="POST" action="{{ route('login') }}">
                @csrf
                <p class="hkicker">Connexion</p>
                <h2>Se connecter</h2>
                <p class="auth-lead">Le téléphone sert d’identifiant. Le mot de passe ouvre votre espace.</p>
                @include('partials.flash')
                <label class="field">
                    <span>Téléphone</span>
                    <span class="field-box">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3.5h8A2.5 2.5 0 0 1 18.5 6v12A2.5 2.5 0 0 1 16 20.5H8A2.5 2.5 0 0 1 5.5 18V6A2.5 2.5 0 0 1 8 3.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 17.5h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        <input name="telephone" value="{{ old('telephone') }}" required autofocus autocomplete="username" placeholder="90 00 00 00">
                    </span>
                    @error('telephone')<small>{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Mot de passe</span>
                    <span class="field-box">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="9" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 10V8a4 4 0 0 1 8 0v2" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="Votre mot de passe">
                    </span>
                    @error('password')<small>{{ $message }}</small>@enderror
                </label>
                <label class="check">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Rester connecté sur cet appareil</span>
                </label>
                <button class="hbtn hbtn-solid auth-submit" type="submit">Entrer dans mon espace</button>
                <div class="demo-box">
                    <p>Essayer un compte · mot de passe <strong>password</strong></p>
                    <button type="button" class="demo-row" data-telephone="90011223" data-password="password"><b>K</b><span>Kossi Agbeko<small>Producteur vérifié · 90011223</small></span></button>
                    <button type="button" class="demo-row" data-telephone="90022334" data-password="password"><b>A</b><span>Afi Dossou<small>Productrice non vérifiée · 90022334</small></span></button>
                    <button type="button" class="demo-row" data-telephone="90033440" data-password="password"><b>K</b><span>Kodjo Amegan<small>Producteur · 90033440</small></span></button>
                    <button type="button" class="demo-row" data-telephone="90033445" data-password="password"><b>A</b><span>Ama Lawson<small>Acheteuse · 90033445</small></span></button>
                    <button type="button" class="demo-row" data-telephone="90000001" data-password="password"><b>A</b><span>Awa Mensah<small>Administratrice · 90000001</small></span></button>
                </div>
                <p class="form-foot">Pas encore de compte ? <a href="{{ route('register') }}">S’inscrire</a></p>
            </form>
        </div>
    </section>
@endsection
