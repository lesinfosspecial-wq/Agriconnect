<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\User;
use App\Support\Lieux;
use App\Support\Urgence;
use Illuminate\Support\Collection;

class MatchingService
{
    public function offresPour(User $acheteur, ?float $rayonKm = null): Collection
    {
        $rayon = $rayonKm ?? (float) ($acheteur->rayon_km ?: 25);

        return Stock::query()
            ->with(['product', 'seller.notesRecues', 'dernierePrediction'])
            ->whereIn('statut', [Stock::PUBLIE, 'partiellement_reserve'])
            ->where('quantite_disponible', '>', 0)
            ->get()
            ->map(function (Stock $stock) use ($acheteur) {
                $stock->distance_km = $this->distance($acheteur, $stock);

                return $stock;
            })
            ->filter(function (Stock $stock) use ($rayon, $acheteur) {
                if ($stock->distance_km === null || $stock->distance_km > $rayon || $stock->availableQuantity() <= 0) {
                    return false;
                }

                return $acheteur->prix_max === null || $stock->seller_price <= $acheteur->prix_max;
            })
            ->sort(function (Stock $a, Stock $b) {
                $urgence = Urgence::priorite($a->urgency) <=> Urgence::priorite($b->urgency);

                return $urgence !== 0 ? $urgence : $a->distance_km <=> $b->distance_km;
            })
            ->values();
    }

    public function toutes(User $acheteur): Collection
    {
        return Stock::query()
            ->with(['product', 'seller.notesRecues', 'dernierePrediction'])
            ->whereIn('statut', [Stock::PUBLIE, 'partiellement_reserve'])
            ->where('quantite_disponible', '>', 0)
            ->get()
            ->map(function (Stock $stock) use ($acheteur) {
                $stock->distance_km = $this->distance($acheteur, $stock);

                return $stock;
            })
            ->sort(function (Stock $a, Stock $b) {
                $urgence = Urgence::priorite($a->urgency) <=> Urgence::priorite($b->urgency);

                return $urgence !== 0 ? $urgence : ($a->distance_km <=> $b->distance_km);
            })
            ->values();
    }

    public function distance(User $acheteur, Stock $stock): ?float
    {
        if ($acheteur->latitude === null || $acheteur->longitude === null || $stock->latitude === null || $stock->longitude === null) {
            return null;
        }

        return Lieux::distanceKm(
            (float) $acheteur->latitude,
            (float) $acheteur->longitude,
            (float) $stock->latitude,
            (float) $stock->longitude,
        );
    }
}
