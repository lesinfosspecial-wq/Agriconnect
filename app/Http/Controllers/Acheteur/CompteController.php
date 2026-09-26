<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use App\Support\Lieux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompteController extends Controller
{
    public function edit(): View
    {
        return view('acheteur.profil', ['quartiers' => array_values(array_filter(Lieux::noms(), fn (string $nom) => $nom !== 'Kpalimé'))]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $quartiers = array_values(array_filter(Lieux::noms(), fn (string $nom) => $nom !== 'Kpalimé'));
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'regex:/^[0-9+\s]{8,20}$/', Rule::unique('users', 'telephone')->ignore($user->id)],
            'quartier' => ['required', Rule::in($quartiers)],
            'rayon_km' => ['required', 'numeric', 'min:1', 'max:80'],
        ]);
        $lieu = Lieux::coordonnees($data['quartier']);
        $user->update([
            'nom' => $data['nom'],
            'telephone' => preg_replace('/\s+/', '', $data['telephone']),
            'quartier' => $data['quartier'],
            'ville' => Lieux::ville($data['quartier']),
            'latitude' => $lieu['latitude'],
            'longitude' => $lieu['longitude'],
            'rayon_km' => $data['rayon_km'],
        ]);

        return back()->with('success', 'Profil enregistré. Les offres proches suivent ce rayon.');
    }

    public function parametres(): View
    {
        return view('acheteur.parametres');
    }

    public function motDePasse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'actuel' => ['required', 'current_password'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user()->update(['mot_de_passe' => $data['mot_de_passe']]);

        return back()->with('success', 'Mot de passe changé.');
    }
}
