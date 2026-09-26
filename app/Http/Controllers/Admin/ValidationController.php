<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityValidation;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ValidationController extends Controller
{
    public function show(Stock $stock): View
    {
        $stock->load(['product', 'seller.notesRecues', 'dernierePrediction', 'orders.buyer']);

        return view('admin.annonces.show', ['stock' => $stock]);
    }

    public function verifier(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Le compte administrateur n’a pas besoin de cette vérification.');
        }

        if ($user->verification === 'verifie') {
            $user->update(['verification' => 'non_verifie']);
            $user->identite()?->delete();

            return back()->with('success', 'Le badge d’identité de '.$user->nom.' a été retiré. Ses stocks restent en ligne.');
        }

        $user->update(['verification' => 'verifie']);
        IdentityValidation::updateOrCreate(
            ['user_id' => $user->id],
            ['admin_id' => auth()->id(), 'cree_le' => now()]
        );

        return back()->with('success', $user->nom.' est maintenant vérifié. Un badge apparaît chez les acheteurs.');
    }

    public function suspendre(Stock $stock): RedirectResponse
    {
        if (in_array($stock->statut, ['vendu', 'expire', 'annule'], true)) {
            return back()->with('error', 'Ce stock est déjà clos. Il ne peut plus être suspendu.');
        }

        if ($stock->statut === 'suspendu') {
            $stock->statut = Stock::PUBLIE;
            $stock->synchroniserDisponibilite();

            return back()->with('success', $stock->product->nom.' est de nouveau visible pour les acheteurs.');
        }

        $stock->update(['statut' => 'suspendu']);

        return back()->with('success', $stock->product->nom.' est suspendu. Les acheteurs ne le voient plus.');
    }
}
