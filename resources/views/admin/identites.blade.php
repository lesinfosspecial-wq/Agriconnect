@extends('layouts.admin')

@section('title', 'Utilisateurs — Agriconnect')
@section('heading', 'Utilisateurs')
@section('lead', 'Vérification d’identité. Le badge informe l’acheteur, il n’empêche pas de vendre.')

@section('main')
    <div class="sheet">
        <table class="adm-table">
            <thead>
                <tr><th>Compte</th><th>Profil</th><th>Téléphone</th><th>Quartier</th><th>Identité</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($identites as $utilisateur)
                    <tr>
                        <td>
                            <div class="who">
                                <span class="letter">{{ mb_strtoupper(mb_substr($utilisateur->nom, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $utilisateur->nom }}</strong>
                                    <small>{{ $utilisateur->ville }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ \App\Support\Libelles::role($utilisateur->role) }}</td>
                        <td>{{ $utilisateur->telephone }}</td>
                        <td>{{ $utilisateur->quartier }}</td>
                        <td>
                            <span class="pill {{ $utilisateur->estVerifie() ? 'pill-ok' : ($utilisateur->verification === 'en_cours' ? 'pill-wait' : 'pill-done') }}">
                                {{ \App\Support\Libelles::verification($utilisateur->verification) }}
                            </span>
                        </td>
                        <td class="acts">
                            <a class="btn-quiet" href="{{ route('admin.identites.show', $utilisateur) }}">Détail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Aucun compte.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
