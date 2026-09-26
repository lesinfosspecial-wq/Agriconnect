<?php

namespace Tests\Feature;

use App\Models\MarketNotification;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantProducteurTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_assistant_propose_puis_enregistre_seulement_apres_oui(): void
    {
        $this->seed();
        $vendeur = User::where('telephone', '90011223')->firstOrFail();
        $avant = Stock::count();

        $this->actingAs($vendeur)
            ->get(route('vendeur.assistant'))
            ->assertOk()
            ->assertSee('Commencer')
            ->assertSee('Questions fréquentes')
            ->assertSee('Bonjour Kossi');

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => '80 kg de tomates à Adidogomé'])
            ->assertRedirect(route('vendeur.assistant'));

        $this->assertSame($avant, Stock::count());

        $this->actingAs($vendeur)
            ->get(route('vendeur.assistant'))
            ->assertOk()
            ->assertSee('Rien')
            ->assertSee('Tomate')
            ->assertSee('80');

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => "oui, c'est bon"])
            ->assertRedirect();

        $this->assertSame($avant + 1, Stock::count());
        $stock = Stock::query()->where('quantite', 80)->whereHas('product', fn ($q) => $q->where('nom', 'Tomate'))->first();
        $this->assertNotNull($stock);
        $this->assertSame('Adidogomé', $stock->quartier);
        $this->assertSame(Stock::PUBLIE, $stock->statut);
        $this->assertGreaterThan(10, $stock->prix_vendeur);
        $this->assertSame(
            User::where('role', 'acheteur')->count(),
            MarketNotification::where('stock_id', $stock->id)->where('type', 'nouveau_stock')->count()
        );
    }

    public function test_un_produit_inconnu_est_redemande_sans_enregistrement(): void
    {
        $this->seed();
        $vendeur = User::where('telephone', '90022334')->firstOrFail();
        $avant = Stock::where('seller_id', $vendeur->id)->count();

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => '40 kg de manioc à 500 FCFA'])
            ->assertRedirect();

        $this->assertSame($avant, Stock::where('seller_id', $vendeur->id)->count());
        $this->actingAs($vendeur)
            ->get(route('vendeur.assistant'))
            ->assertSee('manioc')
            ->assertSee('Tomate');
    }

    public function test_la_discussion_repond_sur_les_ventes_sans_creer_de_stock(): void
    {
        $this->seed();
        $vendeur = User::where('telephone', '90011223')->firstOrFail();
        $avant = Stock::count();

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => 'comment améliorer mes ventes'])
            ->assertRedirect();

        $this->assertSame($avant, Stock::count());
        $this->actingAs($vendeur)
            ->get(route('vendeur.assistant'))
            ->assertSee('Écoulez');

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.vider'))
            ->assertRedirect(route('vendeur.assistant'));

        $this->actingAs($vendeur)
            ->get(route('vendeur.assistant'))
            ->assertOk()
            ->assertSee('Commencer')
            ->assertDontSee('Écoulez');
    }

    public function test_la_conversation_ewe_publie_apres_confirmation(): void
    {
        $this->seed();
        $vendeur = User::where('telephone', '90022334')->firstOrFail();
        $avant = Stock::where('seller_id', $vendeur->id)->count();

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => 'conversation en ewe'])
            ->assertRedirect();

        $this->actingAs($vendeur)
            ->get(route('vendeur.assistant'))
            ->assertSee('Woezɔ')
            ->assertSee('Eʋegbe');

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => '80 kilo timati egbe'])
            ->assertRedirect();

        $this->assertSame($avant, Stock::where('seller_id', $vendeur->id)->count());

        $this->actingAs($vendeur)
            ->get(route('vendeur.assistant'))
            ->assertSee('timáti')
            ->assertSee('80');

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => 'Ẽ'])
            ->assertRedirect();

        $this->assertSame($avant + 1, Stock::where('seller_id', $vendeur->id)->count());
        $this->assertNotNull(Stock::query()->where('seller_id', $vendeur->id)->where('quantite', 80)->whereHas('product', fn ($q) => $q->where('nom', 'Tomate'))->first());
    }

    public function test_une_note_vocale_ewe_ne_montre_pas_le_texte(): void
    {
        $this->seed();
        $vendeur = User::where('telephone', '90022334')->firstOrFail();
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Http::fake([
            'http://127.0.0.1:8765/sante' => \Illuminate\Support\Facades\Http::response(['pret' => true]),
            'http://127.0.0.1:8765/transcrire' => \Illuminate\Support\Facades\Http::response(['texte' => '80 kilo timati egbe']),
            'http://127.0.0.1:8765/parler' => \Illuminate\Support\Facades\Http::response('RIFF', 200, ['Content-Type' => 'audio/wav']),
        ]);

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.message'), ['message' => 'conversation en ewe'])
            ->assertRedirect();

        $this->actingAs($vendeur)
            ->post(route('vendeur.assistant.vocal'), [
                'audio' => \Illuminate\Http\UploadedFile::fake()->create('note.wav', 20, 'audio/wav'),
            ])
            ->assertRedirect();

        $page = $this->actingAs($vendeur)->get(route('vendeur.assistant'));
        $page->assertOk();
        $page->assertDontSee('80 kilo timati', false);
        $page->assertSee('class="voix"', false);
    }
}
