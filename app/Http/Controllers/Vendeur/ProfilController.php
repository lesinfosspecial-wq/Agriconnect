<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Support\Lieux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(): View
    {
        return view('vendeur.profil', [
            'quartiers' => Lieux::noms(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'regex:/^[0-9+\s]{8,20}$/', Rule::unique('users', 'telephone')->ignore($user->id)],
            'quartier' => ['required', Rule::in(Lieux::noms())],
        ], [
            'telephone.unique' => 'Ce numéro est déjà utilisé.',
            'telephone.regex' => 'Indiquez un numéro de téléphone valide.',
        ]);

        $lieu = Lieux::coordonnees($data['quartier']);
        $user->update([
            'nom' => $data['nom'],
            'telephone' => preg_replace('/\s+/', '', $data['telephone']),
            'quartier' => $data['quartier'],
            'ville' => Lieux::ville($data['quartier']),
            'latitude' => $lieu['latitude'],
            'longitude' => $lieu['longitude'],
        ]);

        return back()->with('success', 'Votre profil a été enregistré.');
    }

    public function parametres(): View
    {
        return view('vendeur.parametres');
    }

    public function motDePasse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'actuel' => ['required', 'current_password'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'actuel.current_password' => 'Le mot de passe actuel est incorrect.',
            'mot_de_passe.confirmed' => 'La confirmation ne correspond pas.',
            'mot_de_passe.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
        ]);

        $request->user()->update(['mot_de_passe' => $data['mot_de_passe']]);

        return back()->with('success', 'Le mot de passe a été changé.');
    }
}
