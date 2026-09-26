<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $commandes = Order::query()
            ->with(['buyer', 'stock.product', 'stock.seller'])
            ->where('statut', '!=', Order::ANNULEE)
            ->get();

        $stocks = Stock::query()->with(['product', 'seller', 'dernierePrediction'])->latest('id')->get();
        $actifs = $stocks->whereIn('statut', [Stock::PUBLIE, 'partiellement_reserve']);
        $vendues = $commandes->whereIn('statut', [Order::COLLECTEE, Order::TERMINEE]);

        $jours = collect(range(6, 0))->map(function (int $i) use ($commandes) {
            $date = now()->copy()->subDays($i)->startOfDay();
            $montant = $commandes
                ->filter(fn (Order $order) => $order->created_at && $order->created_at->copy()->startOfDay()->equalTo($date))
                ->sum(fn (Order $order) => $order->quantite * $order->prix_unitaire);

            return ['label' => $date->translatedFormat('d M'), 'montant' => (float) $montant];
        });

        $categories = $actifs
            ->groupBy(fn (Stock $stock) => $stock->product->categorie)
            ->map(fn ($groupe, $nom) => [
                'nom' => $nom,
                'qte' => (float) $groupe->sum(fn (Stock $stock) => $stock->availableQuantity()),
            ])
            ->sortByDesc('qte')
            ->values();

        $topProduits = $commandes
            ->filter(fn (Order $order) => $order->stock)
            ->groupBy(fn (Order $order) => $order->stock->product->nom)
            ->map(fn ($groupe, $nom) => ['nom' => $nom, 'qte' => (float) $groupe->sum('quantite')])
            ->sortByDesc('qte')
            ->take(3)
            ->values();

        $topVendeurs = $commandes
            ->filter(fn (Order $order) => $order->stock)
            ->groupBy(fn (Order $order) => $order->stock->seller->nom)
            ->map(fn ($groupe, $nom) => ['nom' => $nom, 'n' => $groupe->count()])
            ->sortByDesc('n')
            ->take(3)
            ->values();

        $topAcheteurs = $commandes
            ->filter(fn (Order $order) => $order->buyer)
            ->groupBy(fn (Order $order) => $order->buyer->nom)
            ->map(fn ($groupe, $nom) => ['nom' => $nom, 'n' => $groupe->count()])
            ->sortByDesc('n')
            ->take(3)
            ->values();

        return view('admin.dashboard', [
            'enAttente' => User::where('role', '!=', 'admin')->whereIn('verification', ['non_verifie', 'en_cours'])->count(),
            'vendeurs' => User::where('role', 'vendeur')->count(),
            'acheteurs' => User::where('role', 'acheteur')->count(),
            'stocksActifs' => $actifs->count(),
            'ventes' => $vendues->count(),
            'urgents' => $stocks->where('urgence', 'urgent')->whereNotIn('statut', ['vendu', 'annule', 'expire'])->values(),
            'recents' => $stocks->take(5),
            'jours' => $jours,
            'maxJour' => max(1, (float) $jours->max('montant')),
            'categories' => $categories,
            'totalCategorie' => max(1, (float) $categories->sum('qte')),
            'topProduits' => $topProduits,
            'topVendeurs' => $topVendeurs,
            'topAcheteurs' => $topAcheteurs,
        ]);
    }

    public function identites(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('admin.identites', [
            'q' => $q,
            'identites' => User::query()
                ->where('role', '!=', 'admin')
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('nom', 'like', "%{$q}%")
                            ->orWhere('telephone', 'like', "%{$q}%")
                            ->orWhere('quartier', 'like', "%{$q}%");
                    });
                })
                ->withCount(['stocks', 'orders'])
                ->orderByRaw("case verification when 'en_cours' then 0 when 'non_verifie' then 1 else 2 end")
                ->orderBy('nom')
                ->get(),
        ]);
    }

    public function identite(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $user->load([
            'stocks.product',
            'stocks.dernierePrediction',
            'orders.stock.product',
            'orders.stock.seller',
            'orders.pickup',
            'notesRecues',
            'identite.admin',
        ]);

        return view('admin.identites.show', ['personne' => $user]);
    }

    public function annonces(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('admin.annonces.index', [
            'q' => $q,
            'stocks' => Stock::query()
                ->with(['product', 'seller', 'dernierePrediction'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('quartier', 'like', "%{$q}%")
                            ->orWhereHas('product', fn ($product) => $product->where('nom', 'like', "%{$q}%"))
                            ->orWhereHas('seller', fn ($seller) => $seller->where('nom', 'like', "%{$q}%"));
                    });
                })
                ->latest('id')
                ->get(),
        ]);
    }

    public function transactions(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('admin.transactions', [
            'q' => $q,
            'transactions' => Order::query()
                ->with(['buyer', 'stock.product', 'stock.seller', 'pickup'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->whereHas('buyer', fn ($buyer) => $buyer->where('nom', 'like', "%{$q}%"))
                            ->orWhereHas('stock.product', fn ($product) => $product->where('nom', 'like', "%{$q}%"))
                            ->orWhereHas('stock.seller', fn ($seller) => $seller->where('nom', 'like', "%{$q}%"));
                    });
                })
                ->latest('id')
                ->get(),
        ]);
    }

    public function transaction(Order $order): View
    {
        $order->load(['buyer', 'stock.product', 'stock.seller', 'stock.dernierePrediction', 'pickup', 'rating']);

        return view('admin.transactions.show', ['transaction' => $order]);
    }
}
