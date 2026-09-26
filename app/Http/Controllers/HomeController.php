<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $recherche = trim((string) $request->query('q', ''));

        $publiees = Stock::query()
            ->with(['product', 'seller.notesRecues', 'dernierePrediction'])
            ->whereIn('statut', [Stock::PUBLIE, 'partiellement_reserve'])
            ->where('quantite_disponible', '>', 0)
            ->latest('id')
            ->get();

        $offres = $recherche === ''
            ? $publiees
            : $publiees->filter(function (Stock $stock) use ($recherche) {
                $terme = mb_strtolower($recherche);

                return str_contains(mb_strtolower($stock->product->name), $terme)
                    || str_contains(mb_strtolower($stock->product->category), $terme)
                    || str_contains(mb_strtolower($stock->quartier), $terme);
            })->values();

        return view('home', [
            'offres' => $offres,
            'recherche' => $recherche,
            'vendeurs' => User::where('role', 'vendeur')->count(),
            'acheteurs' => User::where('role', 'acheteur')->count(),
            'disponibilites' => (int) round((float) Stock::whereIn('statut', [Stock::PUBLIE, 'partiellement_reserve'])->sum('quantite_disponible')),
        ]);
    }
}
