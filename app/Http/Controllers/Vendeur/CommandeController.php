<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Stock;
use App\Support\Libelles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommandeController extends Controller
{
    public function show(Order $order): View
    {
        $order->load(['stock.product', 'buyer', 'pickup']);
        abort_unless($order->stock && $order->stock->seller_id === auth()->id(), 403);

        return view('vendeur.commandes.show', ['commande' => $order]);
    }

    public function avancer(Order $order): RedirectResponse
    {
        $order->load('stock', 'pickup');
        abort_unless($order->stock && $order->stock->seller_id === auth()->id(), 403);

        $suivant = $order->suivant();

        if ($suivant === null) {
            return back()->with('error', 'Cette commande est déjà terminée.');
        }

        $order->update(['statut' => $suivant]);

        if ($order->pickup && in_array($suivant, ['reservee', 'en_preparation', 'en_collecte', 'collectee', 'terminee'], true)) {
            $order->pickup->update(['statut' => $suivant]);
        }

        if ($suivant === Order::TERMINEE) {
            $stock = $order->stock;
            $stock->quantite_bloquee = max(0, (float) $stock->quantite_bloquee - (float) $order->quantite);
            $stock->save();
            $stock->synchroniserDisponibilite();

            return redirect()
                ->route('vendeur.ventes')
                ->with('success', 'La commande est terminée. Elle apparaît maintenant dans Ventes.');
        }

        return back()->with('success', 'Commande mise à jour : '.Libelles::commande($suivant).'.');
    }

    public function ventes(): View
    {
        $ventes = $this->ventesDuVendeur();
        $parProduit = $ventes
            ->groupBy(fn (Order $order) => $order->stock->product->nom)
            ->map(fn ($groupe, $nom) => ['nom' => $nom, 'qte' => (float) $groupe->sum('quantite')])
            ->sortByDesc('qte');

        return view('vendeur.ventes', [
            'ventes' => $ventes,
            'total' => $ventes->sum(fn (Order $order) => $order->quantite * $order->prix_unitaire),
            'nombre' => $ventes->count(),
            'top' => $parProduit->first(),
            'stocks' => Stock::query()
                ->with('product')
                ->where('seller_id', auth()->id())
                ->where('quantite_disponible', '>', 0)
                ->whereIn('statut', [Stock::PUBLIE, 'partiellement_reserve'])
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function enregistrer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stock_id' => ['required', 'exists:stocks,id'],
            'quantite' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'nom_client' => ['required', 'string', 'max:120'],
        ]);

        $stock = Stock::query()->with('product')->findOrFail($data['stock_id']);
        abort_unless($stock->seller_id === $request->user()->id, 403);

        if (! in_array($stock->statut, [Stock::PUBLIE, 'partiellement_reserve'], true)) {
            return back()->withInput()->with('error', 'Ce stock n’est plus en vente.');
        }

        if ((float) $data['quantite'] > (float) $stock->quantite_disponible) {
            return back()->withInput()->with('error', 'La quantité dépasse ce qu’il reste de libre.');
        }

        $stock->quantite_disponible = (float) $stock->quantite_disponible - (float) $data['quantite'];
        $stock->save();
        $stock->synchroniserDisponibilite();

        Order::create([
            'buyer_id' => null,
            'stock_id' => $stock->id,
            'quantite' => $data['quantite'],
            'prix_unitaire' => $stock->prix_vendeur,
            'statut' => Order::TERMINEE,
            'canal' => 'sur_place',
            'nom_client' => $data['nom_client'],
            'reservee_jusqu_au' => now(),
        ]);

        return redirect()
            ->route('vendeur.ventes')
            ->with('success', 'Vente sur place enregistrée. Le stock a été mis à jour.');
    }

    public function vente(Order $order): View
    {
        $order->load(['stock.product', 'stock.seller', 'buyer', 'pickup']);
        abort_unless($order->stock && $order->stock->seller_id === auth()->id(), 403);
        abort_unless($order->statut === Order::TERMINEE, 404);

        return view('vendeur.ventes.show', ['vente' => $order]);
    }

    public function livraisons(): View
    {
        $livraisons = Order::query()
            ->with(['stock.product', 'buyer', 'pickup'])
            ->where('statut', Order::EN_COLLECTE)
            ->whereHas('stock', fn ($query) => $query->where('seller_id', auth()->id()))
            ->latest()
            ->get();

        return view('vendeur.livraisons', ['livraisons' => $livraisons]);
    }

    private function ventesDuVendeur()
    {
        return Order::query()
            ->with(['stock.product', 'buyer', 'pickup'])
            ->where('statut', Order::TERMINEE)
            ->whereHas('stock', fn ($query) => $query->where('seller_id', auth()->id()))
            ->latest()
            ->get();
    }
}
