<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Pickup;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Support\Lieux;
use Illuminate\Database\Seeder;

class CommandesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $kossi = User::where('telephone', '90011223')->first();
        $ama = User::where('telephone', '90033445')->first();
        $cantine = User::where('telephone', '90055667')->first();

        if (! $kossi || ! $ama || ! $cantine) {
            return;
        }

        $tomate = $this->stockDe($kossi, 'Tomate');
        $mangue = $this->stockDe($kossi, 'Mangue');

        if ($tomate) {
            $this->commande($ama, $tomate, 60, Order::RESERVEE, now()->addDay());
            $this->commande($ama, $tomate, 80, Order::TERMINEE, now()->subDays(3));
            $this->commande($cantine, $tomate, 15, Order::EN_COLLECTE, now()->addHours(4));
        }

        if ($mangue) {
            $this->commande($cantine, $mangue, 20, Order::COLLECTEE, now()->subHours(6));
        }
    }

    private function stockDe(User $vendeur, string $produit): ?Stock
    {
        $product = Product::where('nom', $produit)->first();

        if (! $product) {
            return null;
        }

        return Stock::where('seller_id', $vendeur->id)->where('product_id', $product->id)->first();
    }

    private function commande(User $acheteur, Stock $stock, float $quantite, string $statut, $quand): void
    {
        $existe = Order::query()
            ->where('buyer_id', $acheteur->id)
            ->where('stock_id', $stock->id)
            ->where('quantite', $quantite)
            ->where('statut', $statut)
            ->exists();

        if ($existe || (float) $stock->quantite_disponible < $quantite) {
            return;
        }

        $stock->quantite_disponible = max(0, (float) $stock->quantite_disponible - $quantite);
        if (! in_array($statut, [Order::TERMINEE, Order::ANNULEE], true)) {
            $stock->quantite_bloquee = (float) $stock->quantite_bloquee + $quantite;
        }
        $stock->synchroniserDisponibilite();
        $stock->refresh();

        $order = Order::create([
            'buyer_id' => $acheteur->id,
            'stock_id' => $stock->id,
            'quantite' => $quantite,
            'prix_unitaire' => $stock->prix_vendeur,
            'statut' => $statut,
            'reservee_jusqu_au' => $quand,
        ]);

        Pickup::create([
            'order_id' => $order->id,
            'latitude' => $stock->latitude,
            'longitude' => $stock->longitude,
            'quartier' => $stock->quartier,
            'distance_km' => round(Lieux::distanceKm($acheteur->latitude, $acheteur->longitude, $stock->latitude, $stock->longitude), 2),
            'duree_minutes' => 25,
            'trace' => [
                'depart' => [$acheteur->latitude, $acheteur->longitude],
                'arrivee' => [$stock->latitude, $stock->longitude],
            ],
            'heure_collecte' => $quand,
            'statut' => in_array($statut, [Order::TERMINEE, Order::COLLECTEE], true) ? ($statut === Order::COLLECTEE ? Order::COLLECTEE : Order::TERMINEE) : $statut,
        ]);
    }
}
