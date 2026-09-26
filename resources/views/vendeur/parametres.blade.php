@extends('layouts.producteur')

@section('title', 'Paramètres — Agriconnect')

@section('main')
    @php $user = auth()->user(); @endphp
    <div class="catalog-head">
        <div>
            <h1>Paramètres</h1>
            <p>Sécurité du compte et badge d’identité</p>
        </div>
    </div>

    <div class="prefs">
        <form class="pref" method="POST" action="{{ route('vendeur.parametres.mot-de-passe') }}">
            @csrf
            @method('PUT')
            <h2>Mot de passe</h2>
            <p>Choisissez un mot de passe d’au moins 8 caractères. L’ancien mot de passe est demandé pour confirmer que c’est bien vous.</p>
            <label class="pref-field">
                <span>Mot de passe actuel</span>
                <input type="password" name="actuel" autocomplete="current-password" required>
                @error('actuel')<small>{{ $message }}</small>@enderror
            </label>
            <label class="pref-field">
                <span>Nouveau mot de passe</span>
                <input type="password" name="mot_de_passe" autocomplete="new-password" required>
                @error('mot_de_passe')<small>{{ $message }}</small>@enderror
            </label>
            <label class="pref-field">
                <span>Confirmation</span>
                <input type="password" name="mot_de_passe_confirmation" autocomplete="new-password" required>
            </label>
            <button class="step-btn" type="submit">Mettre à jour</button>
        </form>

        <section class="pref">
            <h2>Badge d’identité</h2>
            <p>Le badge est montré aux acheteurs. Il ne bloque pas la publication d’un stock.</p>
            <span class="pill {{ $user->estVerifie() ? 'pill-dispo' : 'pill-reserve' }}">{{ \App\Support\Libelles::verification($user->verification) }}</span>
            @if (! $user->estVerifie() && $user->verification !== 'en_cours')
                <form method="POST" action="{{ route('vendeur.verification') }}">
                    @csrf
                    <button class="step-btn" type="submit">Demander le badge</button>
                </form>
            @elseif ($user->verification === 'en_cours')
                <p class="pref-status">La demande a été envoyée. L’administrateur peut la valider.</p>
            @else
                <p class="pref-status">Votre identité est déjà confirmée.</p>
            @endif
        </section>

        <section class="pref">
            <h2>Session</h2>
            <p>Fermer la session sur cet appareil. Vous pourrez vous reconnecter avec votre numéro.</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-quiet is-stop" type="submit">Se déconnecter</button>
            </form>
        </section>
    </div>
@endsection
