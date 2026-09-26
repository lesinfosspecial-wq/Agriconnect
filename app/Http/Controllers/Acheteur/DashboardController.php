<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use App\Models\Favori;
use App\Models\Order;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, MatchingService $matching): View
    {
        $toutes = $matching->offresPour(auth()->user());
        $q = trim((string) $request->query('q', ''));
        $categorie = (string) $request->query('categorie', '');
        $categories = $toutes->map(fn ($offre) => $offre->product->categorie)->unique()->filter()->values();

        $offres = $toutes
            ->when($categorie !== '', fn ($liste) => $liste->filter(fn ($offre) => $offre->product->categorie === $categorie)->values())
            ->when($q !== '', function ($liste) use ($q) {
                $terme = mb_strtolower($q);

                return $liste->filter(function ($offre) use ($terme) {
                    return str_contains(mb_strtolower($offre->product->nom.' '.$offre->quartier.' '.$offre->seller->nom), $terme);
                })->values();
            });

        $reservations = Order::query()
            ->with(['stock.product', 'stock.seller', 'pickup'])
            ->where('buyer_id', auth()->id())
            ->whereNotIn('statut', [Order::TERMINEE, Order::ANNULEE])
            ->latest()
            ->get();

        return view('acheteur.dashboard', [
            'offres' => $toutes,
            'reservations' => $reservations,
            'categories' => $categories,
            'categorie' => $categorie,
            'q' => $q,
            'titre' => 'Offres proches de vous',
            'favoris' => Favori::idsPour(auth()->id()),
        ]);
    }

    public function explorer(MatchingService $matching): View
    {
        $offres = $matching->toutes(auth()->user());

        return view('acheteur.dashboard', [
            'offres' => $offres,
            'reservations' => collect(),
            'categories' => $offres->map(fn ($offre) => $offre->product->categorie)->unique()->filter()->values(),
            'categorie' => '',
            'q' => '',
            'titre' => 'Toutes les offres, même loin de vous',
            'favoris' => Favori::idsPour(auth()->id()),
        ]);
    }

    public function notifications(): View
    {
        $notifications = \App\Models\MarketNotification::query()
            ->with('stock')
            ->where('user_id', auth()->id())
            ->latest('id')
            ->get();

        \App\Models\MarketNotification::query()
            ->where('user_id', auth()->id())
            ->where('lu', false)
            ->update(['lu' => true]);

        return view('acheteur.simple', [
            'titre' => 'Notifications',
            'texte' => $notifications->isEmpty() ? 'Aucune notification.' : 'Les nouvelles offres et les changements de prix apparaissent ici.',
            'notifications' => $notifications,
        ]);
    }
}
