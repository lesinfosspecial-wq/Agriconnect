@extends('layouts.acheteur')

@section('title', 'Profil — Agriconnect')

@section('main')
    @php $user = auth()->user(); @endphp
    <div class="hello"><h1>Profil</h1><p>Votre quartier et votre rayon décident des offres affichées.</p></div>
    <form class="book" method="POST" action="{{ route('acheteur.profil.update') }}" style="max-width:460px">
        @csrf
        @method('PUT')
        <label class="field"><span>Nom</span><input name="nom" value="{{ old('nom', $user->nom) }}" required>@error('nom')<small>{{ $message }}</small>@enderror</label>
        <label class="field"><span>Téléphone</span><input name="telephone" value="{{ old('telephone', $user->telephone) }}" required>@error('telephone')<small>{{ $message }}</small>@enderror</label>
        <label class="field"><span>Quartier</span>
            <select name="quartier" required>
                @foreach ($quartiers as $quartier)
                    <option value="{{ $quartier }}" @selected(old('quartier', $user->quartier) === $quartier)>{{ $quartier }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>Rayon (km)</span><input type="number" name="rayon_km" min="1" max="80" step="1" value="{{ old('rayon_km', $user->rayon_km ?: 25) }}" required></label>
        <button class="step-btn" type="submit">Enregistrer</button>
    </form>
@endsection
