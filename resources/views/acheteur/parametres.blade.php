@extends('layouts.acheteur')

@section('title', 'Paramètres — Agriconnect')

@section('main')
    <div class="hello"><h1>Paramètres</h1><p>Mot de passe et session.</p></div>
    <form class="book" method="POST" action="{{ route('acheteur.parametres.mot-de-passe') }}" style="max-width:420px">
        @csrf
        @method('PUT')
        <label class="field"><span>Mot de passe actuel</span><input type="password" name="actuel" required>@error('actuel')<small>{{ $message }}</small>@enderror</label>
        <label class="field"><span>Nouveau mot de passe</span><input type="password" name="mot_de_passe" required>@error('mot_de_passe')<small>{{ $message }}</small>@enderror</label>
        <label class="field"><span>Confirmation</span><input type="password" name="mot_de_passe_confirmation" required></label>
        <button class="step-btn" type="submit">Mettre à jour</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:1rem">
        @csrf
        <button class="step-btn stop" type="submit">Se déconnecter</button>
    </form>
@endsection
