<?php

namespace Database\Seeders;

use App\Models\IdentityValidation;
use App\Models\Order;
use App\Models\Pickup;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Stock;
use App\Models\User;
use App\Services\PricePredictor;
use App\Support\Lieux;
use App\Support\Urgence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->compte('Awa MENSAH', '90000001', 'admin', 'Cacaveli', 'verifie');
        $kossi = $this->compte('Kossi AGBEKO', '90011223', 'vendeur', 'Adidogomé', 'verifie');
        $afi = $this->compte('Afi DOSSOU', '90022334', 'vendeur', 'Bè', 'non_verifie');
        $kodjo = $this->compte('Kodjo AMEGAN', '90033440', 'vendeur', 'Agoè', 'en_cours');
        $ama = $this->compte('Ama LAWSON', '90033445', 'acheteur', 'Tokoin', 'verifie', 25);
        $cantine = $this->compte('Cantine ESIG', '90055667', 'acheteur', 'Bè', 'non_verifie', 20);

        IdentityValidation::create(['user_id' => $admin->id, 'admin_id' => $admin->id, 'cree_le' => now()]);
        IdentityValidation::create(['user_id' => $kossi->id, 'admin_id' => $admin->id, 'cree_le' => now()]);
        IdentityValidation::create(['user_id' => $ama->id, 'admin_id' => $admin->id, 'cree_le' => now()]);

        $produits = [
            ['Tomate', 'Légume', 'kg', 144, 630],
            ['Banane', 'Fruit', 'kg', 96, 350],
            ['Ananas', 'Fruit', 'kg', 168, 480],
            ['Piment', 'Légume', 'kg', 192, 700],
            ['Mangue', 'Fruit', 'kg', 120, 420],
            ['Oignon', 'Légume', 'kg', 480, 280],
        ];

        $catalogue = [];
        foreach ($produits as [$nom, $categorie, $unite, $heures, $base]) {
            $produit = Product::create([
                'nom' => $nom,
                'categorie' => $categorie,
                'unite' => $unite,
                'conservation_heures_reference' => $heures,
            ]);
            $this->historique($produit, $base);
            $catalogue[$produit->slug] = $produit;
        }

        $this->stock($kossi, $catalogue['tomate'], [
            'quantity' => 400, 'quartier' => 'Adidogomé', 'days_left' => 4, 'shelf_days' => 6,
            'freshness' => 4, 'seller_price' => 720, 'min_price' => 600,
        ]);
        $bananes = $this->stock($afi, $catalogue['banane'], [
            'quantity' => 140, 'quartier' => 'Bè', 'days_left' => 2, 'shelf_days' => 4,
            'freshness' => 4, 'seller_price' => 250, 'min_price' => 180,
        ]);
        $ananas = $this->stock($kodjo, $catalogue['ananas'], [
            'quantity' => 150, 'quartier' => 'Agoè', 'days_left' => 6, 'shelf_days' => 8,
            'freshness' => 5, 'seller_price' => 500, 'min_price' => 400,
        ]);
        $this->stock($kossi, $catalogue['mangue'], [
            'quantity' => 80, 'quartier' => 'Kpalimé', 'days_left' => 5, 'shelf_days' => 6,
            'freshness' => 4, 'seller_price' => 450, 'min_price' => 350,
        ]);
        $this->stock($kossi, $catalogue['piment'], [
            'quantity' => 60, 'quartier' => 'Adidogomé', 'days_left' => 7, 'shelf_days' => 8,
            'freshness' => 5, 'seller_price' => 800, 'min_price' => 600,
        ]);
        $this->stock($afi, $catalogue['oignon'], [
            'quantity' => 200, 'quartier' => 'Bè', 'days_left' => 14, 'shelf_days' => 20,
            'freshness' => 5, 'seller_price' => 280, 'min_price' => 200,
        ]);

        $this->commande($ama, $bananes, 40, Order::EN_PREPARATION, now()->addHours(2));
        $terminee = $this->commande($cantine, $ananas, 25, Order::TERMINEE, now()->subDay());
        Rating::create([
            'auteur_id' => $cantine->id,
            'cible_id' => $kodjo->id,
            'order_id' => $terminee->id,
            'note' => 5,
            'commentaire' => 'Ananas bien mûrs, collecte facile.',
        ]);

        $this->call(CommandesDemoSeeder::class);
    }

    private function compte(string $nom, string $telephone, string $role, string $quartier, string $verification, ?float $rayon = null): User
    {
        $lieu = Lieux::coordonnees($quartier);

        return User::create([
            'nom' => $nom,
            'telephone' => $telephone,
            'mot_de_passe' => 'password',
            'role' => $role,
            'quartier' => $quartier,
            'ville' => Lieux::ville($quartier),
            'latitude' => $lieu['latitude'],
            'longitude' => $lieu['longitude'],
            'verification' => $verification,
            'rayon_km' => $role === 'acheteur' ? $rayon : null,
        ]);
    }

    private function historique(Product $product, float $base): void
    {
        $lignes = [];

        for ($i = 0; $i < 36; $i++) {
            $mois = ($i % 12) + 1;
            $fraicheur = [5, 4, 4, 3, 3, 2][$i % 6];
            $jours = [8, 6, 5, 3, 2, 1][$i % 6];
            $urgence = Urgence::classify($jours, $fraicheur);
            $demande = 0.35 + ($i % 5) * 0.1;
            $offre = 0.30 + (($i + 2) % 5) * 0.1;
            $quantite = [40, 80, 150, 300, 500, 800][$i % 6];

            $lignes[] = [
                'product_id' => $product->id,
                'month' => $mois,
                'freshness' => $fraicheur,
                'days_left' => $jours,
                'urgency' => $urgence,
                'demand' => $demande,
                'supply' => $offre,
                'quantity' => $quantite,
                'price' => round($this->prixHistorique($base, $mois, $fraicheur, $jours, $urgence, $demande, $offre, $quantite, $i), 2),
            ];
        }

        PriceHistory::insert($lignes);
    }

    private function prixHistorique(float $base, int $mois, int $fraicheur, int $jours, string $urgence, float $demande, float $offre, float $quantite, int $index): float
    {
        $saison = sin(2 * M_PI * $mois / 12) * 35 + cos(2 * M_PI * $mois / 12) * 20;
        $bruit = sin($index * 1.3) * 8;

        return max(80, $base
            + ($jours * 8)
            + ($fraicheur * 22)
            - (Urgence::poids($urgence) * 65)
            + (($demande - $offre) * 110)
            - (log(1 + $quantite) * 10)
            + $saison
            + $bruit);
    }

    private function stock(User $vendeur, Product $product, array $attrs): Stock
    {
        $duree = $attrs['shelf_days'];
        $jours = $attrs['days_left'];
        $recolte = now()->startOfDay()->subDays(max(0, $duree - $jours));
        $lieu = Lieux::coordonnees($attrs['quartier']);

        $stock = Stock::create([
            'seller_id' => $vendeur->id,
            'product_id' => $product->id,
            'quantite' => $attrs['quantity'],
            'quantite_disponible' => $attrs['quantity'],
            'quantite_bloquee' => 0,
            'unite' => $product->unite,
            'quartier' => $attrs['quartier'],
            'ville' => Lieux::ville($attrs['quartier']),
            'latitude' => $lieu['latitude'],
            'longitude' => $lieu['longitude'],
            'recolte_le' => $recolte,
            'expiration_estimee' => $recolte->copy()->addDays($duree),
            'fraicheur' => $attrs['freshness'],
            'prix_souhaite' => $attrs['seller_price'] ?? 100,
            'prix_vendeur' => $attrs['seller_price'] ?? 100,
            'prix_minimum' => $attrs['min_price'] ?? ($attrs['seller_price'] ?? 100) * 0.8,
            'urgence' => 'normal',
            'statut' => Stock::PUBLIE,
            'mode_collecte' => $attrs['pickup_mode'] ?? 'sur_place',
        ]);

        $prediction = app(PricePredictor::class)->estimate($product, [
            'days_left' => $jours,
            'freshness' => $attrs['freshness'],
            'quantity' => $attrs['quantity'],
        ]);
        $stock->appliquerPrediction($prediction);

        if (! isset($attrs['seller_price'])) {
            $stock->update([
                'prix_vendeur' => $prediction['recommended'],
                'prix_souhaite' => $prediction['recommended'],
            ]);
        }

        return $stock->fresh();
    }

    private function commande(User $acheteur, Stock $stock, float $quantite, string $statut, $quand): Order
    {
        $stock->quantite_disponible = max(0, (float) $stock->quantite_disponible - $quantite);
        if ($statut !== Order::TERMINEE) {
            $stock->quantite_bloquee = (float) $stock->quantite_bloquee + $quantite;
        }
        $stock->synchroniserDisponibilite();

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
            'statut' => in_array($statut, ['annulee', 'expiree'], true) ? 'terminee' : $statut,
        ]);

        return $order;
    }
}
