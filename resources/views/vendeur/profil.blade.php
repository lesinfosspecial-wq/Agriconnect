@extends('layouts.producteur')

@section('title', 'Mon profil — Agriconnect')

@section('main')
    @php
        $user = auth()->user();
        $prenom = strtok((string) $user->nom, ' ') ?: 'Producteur';
    @endphp

    <section class="id-banner">
        <span class="letter lg">{{ mb_strtoupper(mb_substr($prenom, 0, 1)) }}</span>
        <div>
            <p>Compte producteur</p>
            <h1>{{ $user->nom }}</h1>
            <span class="pill {{ $user->estVerifie() ? 'pill-light' : 'pill-light-warn' }}">{{ \App\Support\Libelles::verification($user->verification) }}</span>
        </div>
    </section>

    <div class="id-facts">
        <div><span>Téléphone</span><strong>{{ $user->telephone }}</strong></div>
        <div><span>Quartier</span><strong>{{ $user->quartier }}</strong></div>
        <div><span>Ville</span><strong>{{ $user->ville }}</strong></div>
        <div><span>Membre depuis</span><strong>{{ $user->created_at?->translatedFormat('d F Y') }}</strong></div>
    </div>

    <form class="sheet account-form" method="POST" action="{{ route('vendeur.profil.update') }}">
        @csrf
        @method('PUT')
        <header>
            <div>
                <h2>Coordonnées</h2>
                <p>Ces informations servent à vous identifier et à situer vos stocks pour les acheteurs proches.</p>
            </div>
        </header>
        <div class="field-grid">
            <label>
                <span>Nom complet</span>
                <input type="text" name="nom" value="{{ old('nom', $user->nom) }}" maxlength="120" required>
                @error('nom')<small>{{ $message }}</small>@enderror
            </label>
            <label>
                <span>Téléphone</span>
                <input type="text" name="telephone" value="{{ old('telephone', $user->telephone) }}" required>
                @error('telephone')<small>{{ $message }}</small>@enderror
            </label>
            <label>
                <span>Quartier</span>
                <select name="quartier" required>
                    @foreach ($quartiers as $quartier)
                        <option value="{{ $quartier }}" @selected(old('quartier', $user->quartier) === $quartier)>{{ $quartier }}</option>
                    @endforeach
                </select>
                @error('quartier')<small>{{ $message }}</small>@enderror
            </label>
        </div>
        <footer>
            <button class="step-btn" type="submit">Enregistrer les modifications</button>
        </footer>
    </form>
@endsection
