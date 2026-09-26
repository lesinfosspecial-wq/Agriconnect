@extends('layouts.accueil')

@section('title', 'Accès refusé — Agriconnect')

@section('main')
    <section class="auth-screen auth-screen-single">
        <div class="auth-panel">
            <div class="auth-card">
                <p class="hkicker">Accès refusé</p>
                <h2>Cet espace n’est pas le vôtre.</h2>
                <p class="auth-lead">Connectez-vous avec le profil qui correspond à la page demandée.</p>
                <a class="hbtn hbtn-solid auth-submit" href="{{ auth()->check() ? auth()->user()->espaceRoute() : route('login') }}">Retourner à mon espace</a>
            </div>
        </div>
    </section>
@endsection
