<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Lieux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function choose(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['vendeur', 'acheteur'])],
        ]);

        $request->session()->put('inscription_role', $data['role']);

        return redirect()->route('register.form');
    }

    public function form(Request $request): View|RedirectResponse
    {
        $role = $request->session()->get('inscription_role');

        if (! in_array($role, ['vendeur', 'acheteur'], true)) {
            return redirect()->route('register');
        }

        return view('auth.register-form', [
            'role' => $role,
            'quartiers' => array_values(array_filter(Lieux::noms(), fn (string $nom) => $nom !== 'Kpalimé')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $request->session()->get('inscription_role');
        $quartiers = array_values(array_filter(Lieux::noms(), fn (string $nom) => $nom !== 'Kpalimé'));

        $rules = [
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'regex:/^[0-9+\s]{8,20}$/', 'unique:users,telephone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'quartier' => ['required', Rule::in($quartiers)],
        ];

        if ($role === 'vendeur') {
            $rules['photo'] = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'];
            $rules['piece'] = ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];
        }

        if ($role === 'acheteur') {
            $rules['quantite_recherchee'] = ['nullable', 'numeric', 'min:0'];
            $rules['prix_max'] = ['nullable', 'numeric', 'min:0'];
            $rules['rayon_km'] = ['nullable', 'numeric', 'min:1', 'max:100'];
        }

        if (! in_array($role, ['vendeur', 'acheteur'], true)) {
            return redirect()->route('register');
        }

        $data = $request->validate($rules);
        $coordonnees = Lieux::coordonnees($data['quartier']);
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $gps = is_numeric($latitude) && is_numeric($longitude);
        $telephone = preg_replace('/\s+/', '', $data['telephone']);

        $user = User::create([
            'nom' => $data['nom'],
            'telephone' => $telephone,
            'mot_de_passe' => $data['password'],
            'role' => $role,
            'quartier' => $data['quartier'],
            'ville' => Lieux::ville($data['quartier']),
            'latitude' => $gps ? $latitude : ($coordonnees['latitude'] ?? null),
            'longitude' => $gps ? $longitude : ($coordonnees['longitude'] ?? null),
            'verification' => $role === 'vendeur' ? 'en_cours' : 'non_verifie',
            'quantite_recherchee' => $data['quantite_recherchee'] ?? null,
            'prix_max' => $data['prix_max'] ?? null,
            'rayon_km' => $role === 'acheteur' ? ($data['rayon_km'] ?? 25) : null,
            'photo_profil' => $role === 'vendeur' ? $request->file('photo')->store('profils', 'public') : null,
            'piece_agriculteur' => $role === 'vendeur' ? $request->file('piece')->store('pieces', 'public') : null,
        ]);

        $request->session()->forget('inscription_role');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($user->espaceRoute())->with('success', $role === 'vendeur'
            ? 'Compte créé. Votre photo et votre pièce sont envoyées à l’administrateur pour le badge. Vous pouvez déjà publier un stock.'
            : 'Compte créé. Vous pouvez publier tout de suite. La vérification d’identité est un badge, elle ne bloque pas la vente.');
    }
}
