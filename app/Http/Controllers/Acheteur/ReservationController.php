<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Pickup;
use App\Models\Rating;
use App\Models\Stock;
use App\Services\MatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(): View
    {
        $reservations = Order::query()
            ->with(['stock.product', 'stock.seller.notesRecues', 'pickup', 'rating'])
            ->where('buyer_id', auth()->id())
            ->latest()
            ->get();

        return view('acheteur.reservations', ['reservations' => $reservations]);
    }

    public function show(Order $order): View
    {
        abort_unless($order->buyer_id === auth()->id(), 403);
        $order->load(['stock.product', 'stock.seller', 'pickup', 'rating']);

        return view('acheteur.reservations.show', ['commande' => $order]);
    }

    public function store(Request $request, Stock $stock, MatchingService $matching): RedirectResponse
    {
        abort_unless(in_array($stock->statut, [Stock::PUBLIE, 'partiellement_reserve'], true), 404);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.5'],
        ]);

        $distance = $matching->distance($request->user(), $stock);
        $rayon = (float) ($request->user()->rayon_km ?: 25);

        if ($distance === null || $distance > $rayon) {
            return back()->with('error', 'Cette offre est hors de votre rayon de collecte.');
        }

        $order = DB::transaction(function () use ($request, $stock, $data, $distance) {
            $locked = Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();

            if ((float) $data['quantity'] > (float) $locked->quantite_disponible) {
                return null;
            }

            $echeance = now()->addHours($locked->urgence === 'urgent' ? 3 : 24);
            $locked->quantite_disponible = (float) $locked->quantite_disponible - (float) $data['quantity'];
            $locked->quantite_bloquee = (float) $locked->quantite_bloquee + (float) $data['quantity'];
            $locked->synchroniserDisponibilite();

            $order = Order::create([
                'buyer_id' => $request->user()->id,
                'stock_id' => $locked->id,
                'quantite' => $data['quantity'],
                'prix_unitaire' => $locked->prix_vendeur,
                'statut' => Order::RESERVEE,
                'reservee_jusqu_au' => $echeance,
            ]);

            Pickup::create([
                'order_id' => $order->id,
                'latitude' => $locked->latitude,
                'longitude' => $locked->longitude,
                'quartier' => $locked->quartier,
                'distance_km' => round($distance, 2),
                'duree_minutes' => max(10, (int) round($distance * 4)),
                'trace' => [
                    'depart' => [$request->user()->latitude, $request->user()->longitude],
                    'arrivee' => [$locked->latitude, $locked->longitude],
                ],
                'heure_collecte' => $echeance,
                'statut' => Order::RESERVEE,
            ]);

            return $order;
        });

        if ($order === null) {
            return back()->with('error', 'La quantité demandée dépasse le stock encore disponible.')->withInput();
        }

        return redirect()
            ->route('acheteur.reservations.index')
            ->with('success', 'Réservation confirmée. Le stock est bloqué jusqu’à la collecte.');
    }

    public function annuler(Order $order): RedirectResponse
    {
        abort_unless($order->buyer_id === auth()->id(), 403);

        if ($order->statut !== Order::RESERVEE) {
            return back()->with('error', 'Seul un statut « réservé » peut encore être annulé.');
        }

        DB::transaction(function () use ($order) {
            $stock = Stock::query()->whereKey($order->stock_id)->lockForUpdate()->first();
            if ($stock) {
                $stock->quantite_disponible = (float) $stock->quantite_disponible + (float) $order->quantite;
                $stock->quantite_bloquee = max(0, (float) $stock->quantite_bloquee - (float) $order->quantite);
                $stock->synchroniserDisponibilite();
            }
            $order->update(['statut' => Order::ANNULEE]);
        });

        return back()->with('success', 'Réservation annulée. La quantité est de nouveau disponible.');
    }

    public function noter(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->buyer_id === auth()->id(), 403);
        $order->load('stock', 'rating');

        if (! $order->peutEtreNotee()) {
            return back()->with('error', 'La note est possible une fois la collecte terminée.');
        }

        $data = $request->validate([
            'note' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        Rating::create([
            'auteur_id' => $request->user()->id,
            'cible_id' => $order->stock->seller_id,
            'order_id' => $order->id,
            'note' => $data['note'],
            'commentaire' => $data['commentaire'] ?? null,
        ]);

        return back()->with('success', 'Merci, votre note a été enregistrée.');
    }

    public function demanderVerification(Request $request): RedirectResponse
    {
        $request->user()->demanderBadge();

        return back()->with('success', 'Demande d’identité envoyée.');
    }
}
