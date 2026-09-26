@extends('layouts.accueil')

@section('title', 'Inscription — Agriconnect')

@section('main')
    <section class="auth-screen">
        <aside class="auth-visual {{ $role === 'vendeur' ? 'is-portrait' : '' }}">
            <img src="{{ asset($role === 'vendeur' ? 'images/home/hero-farmer.jpg' : 'images/home/produit-legumes.jpg') }}" alt="">
            <div class="auth-visual-shade"></div>
            <div class="auth-visual-copy">
                <p class="hpill">Étape 2 sur 2</p>
                <h1>{{ $role === 'vendeur' ? 'Producteur / vendeur' : 'Acheteur' }}</h1>
                <p>{{ $role === 'vendeur' ? 'Le badge d’identité rassure les acheteurs. Il ne bloque pas la publication.' : 'Le quartier et le rayon servent à vous montrer les récoltes que vous pouvez collecter.' }}</p>
            </div>
        </aside>
        <div class="auth-panel">
            <form class="auth-card auth-card-wide" method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="latitude" data-geo-lat value="">
                <input type="hidden" name="longitude" data-geo-lng value="">
                <ol class="auth-steps">
                    <li class="done"><a href="{{ route('register') }}">Profil</a></li>
                    <li class="now">Informations</li>
                </ol>
                <p class="hkicker">{{ $role === 'vendeur' ? 'Producteur / vendeur' : 'Acheteur' }}</p>
                <h2>Vos informations</h2>
                <p class="auth-lead"><a href="{{ route('register') }}">Changer de profil</a></p>
                @include('partials.flash')
                <div class="form-grid">
                    <label class="field">
                        <span>Nom complet</span>
                        <span class="field-box">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M6 19c1.2-2.6 3.2-3.8 6-3.8s4.8 1.2 6 3.8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            <input name="nom" value="{{ old('nom') }}" required maxlength="120" placeholder="Kossi Agbeko">
                        </span>
                        @error('nom')<small>{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Téléphone</span>
                        <span class="field-box">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3.5h8A2.5 2.5 0 0 1 18.5 6v12A2.5 2.5 0 0 1 16 20.5H8A2.5 2.5 0 0 1 5.5 18V6A2.5 2.5 0 0 1 8 3.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 17.5h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            <input name="telephone" value="{{ old('telephone') }}" required placeholder="90 00 00 00">
                        </span>
                        @error('telephone')<small>{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Quartier</span>
                        <span class="field-box">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="11" r="1.8" fill="currentColor"/></svg>
                            <select name="quartier" required>
                                <option value="">Choisir un quartier</option>
                                @foreach ($quartiers as $quartier)
                                    <option value="{{ $quartier }}" @selected(old('quartier') === $quartier)>{{ $quartier }}</option>
                                @endforeach
                            </select>
                        </span>
                        @error('quartier')<small>{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Mot de passe</span>
                        <span class="field-box">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="9" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 10V8a4 4 0 0 1 8 0v2" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                            <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="8 caractères minimum">
                        </span>
                        @error('password')<small>{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Confirmation</span>
                        <span class="field-box">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.2 4.2L19 7.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Répéter le mot de passe">
                        </span>
                    </label>
                </div>
                @if ($role === 'vendeur')
                    <fieldset class="pref-box">
                        <legend>Pièces pour le badge</legend>
                        <p class="auth-lead">L’administrateur s’appuie sur ces fichiers pour valider le compte. Cela ne bloque pas la publication d’un stock.</p>
                        <div class="form-grid">
                            <label class="field">
                                <span>Photo de profil</span>
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                @error('photo')<small>{{ $message }}</small>@enderror
                            </label>
                            <label class="field">
                                <span>Preuve d'activité</span>
                                <input type="file" name="piece" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                @error('piece')<small>{{ $message }}</small>@enderror
                            </label>
                        </div>
                    </fieldset>
                @endif
                @if ($role === 'acheteur')
                    <fieldset class="pref-box">
                        <legend>Préférences de collecte</legend>
                        <div class="form-grid">
                            <label class="field">
                                <span>Quantité recherchée</span>
                                <input type="number" name="quantite_recherchee" min="0" step="1" value="{{ old('quantite_recherchee') }}" placeholder="Facultatif">
                            </label>
                            <label class="field">
                                <span>Prix max / unité</span>
                                <input type="number" name="prix_max" min="0" step="5" value="{{ old('prix_max') }}" placeholder="Facultatif">
                            </label>
                            <label class="field">
                                <span>Rayon (km)</span>
                                <input type="number" name="rayon_km" min="1" max="100" step="1" value="{{ old('rayon_km', 25) }}">
                            </label>
                        </div>
                    </fieldset>
                @endif
                <button class="hbtn hbtn-solid auth-submit" type="submit">Créer mon espace</button>
            </form>
        </div>
    </section>
    @include('partials.geoloc')
@endsection
