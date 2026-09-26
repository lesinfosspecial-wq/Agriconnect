@extends('layouts.accueil')

@section('title', 'Inscription — Agriconnect')

@section('main')
    <section class="choice-wrap">
        <div class="choice-head">
            <p class="hkicker">Inscription</p>
            <h1>Je suis</h1>
            <p>Un clic ouvre le formulaire. Un producteur peut publier un stock même sans badge d’identité.</p>
        </div>
        <form method="POST" action="{{ route('register.choose') }}">
            @csrf
            <div class="choice-grid">
                <button class="choice-card" type="submit" name="role" value="vendeur">
                    <img src="{{ asset('images/home/hero-farmer.jpg') }}" alt="">
                    <span>
                        <small>Profil</small>
                        <strong>Producteur / vendeur</strong>
                        <em>Je déclare un stock à écouler</em>
                    </span>
                </button>
                <button class="choice-card" type="submit" name="role" value="acheteur">
                    <img src="{{ asset('images/home/produit-legumes.jpg') }}" alt="">
                    <span>
                        <small>Profil</small>
                        <strong>Acheteur</strong>
                        <em>Je cherche des produits proches</em>
                    </span>
                </button>
            </div>
            @error('role')<p class="field-error">{{ $message }}</p>@enderror
        </form>
        <p class="choice-foot">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
    </section>
@endsection
