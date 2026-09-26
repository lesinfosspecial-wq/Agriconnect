<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParcoursAgriconnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_pages_publiques_s_affichent(): void
    {
        $this->get('/')->assertOk()->assertSee('Agriconnect');
        $this->get('/connexion')->assertOk()->assertSee('Se connecter');
        $this->get('/inscription')->assertOk()->assertSee('Producteur');
    }

    public function test_un_producteur_peut_s_inscrire(): void
    {
        Storage::fake('public');

        $photo = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCfAAf/2Q==');

        $this->post('/inscription/profil', ['role' => 'vendeur'])->assertRedirect(route('register.form'));
        $this->post('/inscription', [
            'nom' => 'Yao Mensah',
            'telephone' => '90112233',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'quartier' => 'Agoè',
            'photo' => UploadedFile::fake()->createWithContent('profil.jpg', $photo),
            'piece' => UploadedFile::fake()->createWithContent('carte.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
        ])->assertRedirect(route('vendeur.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'telephone' => '90112233',
            'role' => 'vendeur',
            'quartier' => 'Agoè',
            'verification' => 'en_cours',
        ]);
    }

    public function test_chaque_profil_reste_dans_son_espace(): void
    {
        $this->seed();

        $vendeur = User::where('telephone', '90011223')->firstOrFail();
        $this->actingAs($vendeur)->get('/admin')->assertForbidden();
        $this->actingAs($vendeur)->get('/vendeur')->assertOk()->assertSee('Tomate');
        $tomate = Stock::whereHas('product', fn ($query) => $query->where('nom', 'Tomate'))->firstOrFail();
        $this->actingAs($vendeur)->get(route('vendeur.stocks.show', $tomate))->assertOk()->assertSee('Régression linéaire');
        $this->actingAs($vendeur)->get(route('vendeur.stocks.create'))->assertOk()->assertSee('Déclarer un stock');

        $admin = User::where('telephone', '90000001')->firstOrFail();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Tableau de bord')->assertDontSee('90022334');
        $this->actingAs($admin)->get(route('admin.identites'))->assertOk()->assertSee('Vérification d’identité')->assertSee('90022334');

        $acheteur = User::where('telephone', '90033445')->firstOrFail();
        $this->actingAs($acheteur)
            ->get('/acheteur')
            ->assertOk()
            ->assertSee('Banane')
            ->assertSee('Identité non vérifiée')
            ->assertDontSee('Kpalimé');

        $banane = Stock::whereHas('product', fn ($query) => $query->where('nom', 'Banane'))->firstOrFail();
        $this->actingAs($acheteur)->get(route('acheteur.offres.show', $banane))->assertOk()->assertSee('Réserver');
        $this->actingAs($acheteur)->get(route('acheteur.reservations.index'))->assertOk()->assertSee('Banane');
        $this->actingAs($admin)->get(route('admin.annonces.show', $tomate))->assertOk()->assertSee('Identité vérifiée');
    }

    public function test_le_parcours_declaration_validation_reservation(): void
    {
        $this->seed();

        $vendeur = User::where('telephone', '90011223')->firstOrFail();
        $oignon = Product::where('nom', 'Oignon')->firstOrFail();

        $this->actingAs($vendeur)->post('/vendeur/stocks', [
            'product_id' => $oignon->id,
            'quantity' => 100,
            'quartier' => 'Adidogomé',
            'harvested_on' => now()->toDateString(),
            'seller_price' => 400,
            'min_price' => 300,
            'pickup_mode' => 'sur_place',
        ])->assertRedirect();

        $stock = Stock::where('seller_id', $vendeur->id)->where('product_id', $oignon->id)->latest('id')->firstOrFail();
        $this->assertSame(Stock::PUBLIE, $stock->statut);
        $this->assertNull($stock->ai_price);
        $acheteurs = User::where('role', 'acheteur')->pluck('id');
        $this->assertSame($acheteurs->count(), \App\Models\MarketNotification::where('stock_id', $stock->id)->where('type', 'nouveau_stock')->count());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $acheteurs->first(),
            'stock_id' => $stock->id,
            'type' => 'nouveau_stock',
            'message' => 'Oignon frais disponible à Adidogomé : 400 FCFA/kg.',
        ]);

        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCfAAf/2Q==');
        $this->actingAs($vendeur)->put(route('vendeur.stocks.update', $stock), [
            'product_id' => $oignon->id,
            'quantity' => 100,
            'quartier' => 'Adidogomé',
            'harvested_on' => now()->toDateString(),
            'seller_price' => 400,
            'min_price' => 300,
            'pickup_mode' => 'sur_place',
            'photo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('oignon.jpg', $jpeg),
        ])->assertRedirect(route('vendeur.stocks.show', $stock));

        $stock->refresh();
        $this->assertGreaterThan(50, $stock->ai_price);
        $this->assertNotNull($stock->vision);
        $this->assertSame(0, \App\Models\MarketNotification::where('stock_id', $stock->id)->where('type', 'changement_prix')->count());

        $this->actingAs($vendeur)->post(route('vendeur.stocks.ajuster', $stock), [
            'seller_price' => 350,
        ])->assertRedirect();

        $this->assertSame($acheteurs->count(), \App\Models\MarketNotification::where('stock_id', $stock->id)->where('type', 'changement_prix')->count());
        $this->assertDatabaseHas('notifications', [
            'stock_id' => $stock->id,
            'type' => 'changement_prix',
            'message' => 'Oignon à Adidogomé : le prix vient de changer, il est maintenant de 350 FCFA/kg.',
        ]);

        $acheteur = User::where('telephone', '90033445')->firstOrFail();
        $this->actingAs($acheteur)->post(route('acheteur.reservations.store', $stock), [
            'quantity' => 10,
        ])->assertRedirect(route('acheteur.reservations.index'));

        $this->assertDatabaseHas('orders', [
            'stock_id' => $stock->id,
            'buyer_id' => $acheteur->id,
            'statut' => 'reservee',
        ]);
    }
}
