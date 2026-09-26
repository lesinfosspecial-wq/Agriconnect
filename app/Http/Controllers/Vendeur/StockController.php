<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\MarketNotification;
use App\Models\PriceAdjustment;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\FreshnessVision;
use App\Services\PricePredictor;
use App\Support\Lieux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockController extends Controller
{
    public function create(): View
    {
        return view('vendeur.stocks.create', [
            'products' => Product::orderBy('nom')->get(),
            'quartiers' => Lieux::noms(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, false);
        $product = Product::findOrFail($data['product_id']);
        $lieu = Lieux::coordonnees($data['quartier']);
        $harvested = Carbon::parse($data['harvested_on'])->startOfDay();
        $expires = $harvested->copy()->addHours((int) $product->conservation_heures_reference);
        $jours = max(0, (int) floor(($expires->timestamp - now()->startOfDay()->timestamp) / 86400));

        $stock = new Stock([
            'seller_id' => $request->user()->id,
            'product_id' => $product->id,
            'quantite' => $data['quantity'],
            'quantite_disponible' => $data['quantity'],
            'quantite_bloquee' => 0,
            'unite' => $product->unite,
            'quartier' => $data['quartier'],
            'ville' => Lieux::ville($data['quartier']),
            'latitude' => $lieu['latitude'],
            'longitude' => $lieu['longitude'],
            'recolte_le' => $harvested,
            'expiration_estimee' => $expires,
            'fraicheur' => 5,
            'prix_souhaite' => $data['seller_price'],
            'prix_vendeur' => $data['seller_price'],
            'prix_minimum' => $data['min_price'],
            'statut' => Stock::PUBLIE,
            'mode_collecte' => $data['pickup_mode'],
            'urgence' => \App\Support\Urgence::classify($jours, 5),
        ]);

        if ($request->hasFile('photo')) {
            $stock->photo_url = $request->file('photo')->store('stocks', 'public');
        }

        $stock->save();
        $this->prevenirAcheteurs($stock, 'nouveau_stock', $stock->product->nom.' frais disponible à '.$stock->quartier.' : '.fcfa($stock->prix_vendeur).'/'.$stock->unite.'.');

        return redirect()
            ->route('vendeur.stocks.show', $stock)
            ->with('success', 'Stock publié. Limite estimée le '.$expires->translatedFormat('d F Y').' selon la conservation du produit. Tous les acheteurs ont été prévenus. Aucun prix n’est proposé tant que vous ne modifiez pas l’offre.');
    }

    public function edit(Stock $stock): View
    {
        $this->autoriser($stock);

        return view('vendeur.stocks.edit', [
            'stock' => $stock->load('product'),
            'products' => Product::orderBy('nom')->get(),
            'quartiers' => Lieux::noms(),
        ]);
    }

    public function update(Request $request, Stock $stock, FreshnessVision $vision, PricePredictor $predictor): RedirectResponse
    {
        $this->autoriser($stock);

        if (in_array($stock->statut, ['vendu', 'annule', 'expire', 'rejete'], true)) {
            return back()->with('error', 'Ce stock ne peut plus être modifié.');
        }

        $data = $this->validated($request, true);
        $ancienPrix = (float) $stock->prix_vendeur;
        $product = Product::findOrFail($data['product_id']);
        $lieu = Lieux::coordonnees($data['quartier']);
        $harvested = Carbon::parse($data['harvested_on'])->startOfDay();
        $expires = $harvested->copy()->addHours((int) $product->conservation_heures_reference);
        $bloque = (float) $stock->quantite_bloquee;

        if ((float) $data['quantity'] < $bloque) {
            return back()->withInput()->with('error', 'La quantité ne peut pas descendre sous ce qui est déjà réservé.');
        }

        if ($request->hasFile('photo')) {
            $stock->photo_url = $request->file('photo')->store('stocks', 'public');
        }

        $stock->fill([
            'product_id' => $product->id,
            'quantite' => $data['quantity'],
            'quantite_disponible' => (float) $data['quantity'] - $bloque,
            'unite' => $product->unite,
            'quartier' => $data['quartier'],
            'ville' => Lieux::ville($data['quartier']),
            'latitude' => $lieu['latitude'],
            'longitude' => $lieu['longitude'],
            'recolte_le' => $harvested,
            'expiration_estimee' => $expires,
            'prix_souhaite' => $data['seller_price'],
            'prix_vendeur' => $data['seller_price'],
            'prix_minimum' => $data['min_price'],
            'mode_collecte' => $data['pickup_mode'],
        ]);
        $stock->save();
        $stock->setRelation('product', $product);

        $lecture = $vision->analyser($stock, (string) $stock->photo_url);
        $stock->update(['fraicheur' => $lecture['fraicheur']]);
        $prediction = $predictor->estimate($product, [
            'days_left' => $stock->fresh()->joursRestants(),
            'freshness' => $lecture['fraicheur'],
            'quantity' => (float) $data['quantity'],
        ]);
        $prediction['vision'] = $lecture;
        $stock->appliquerPrediction($prediction);

        $prixChange = abs($ancienPrix - (float) $stock->prix_vendeur) > 0.001;
        if ($prixChange) {
            $this->alerterPrix($stock);
        }

        return redirect()
            ->route('vendeur.stocks.show', $stock)
            ->with('success', $prixChange
                ? 'Analyse terminée. Les acheteurs ont été prévenus que seul le prix a changé.'
                : 'Analyse terminée. La photo et votre prix ont été comparés à l’estimation.');
    }

    public function show(Stock $stock): View
    {
        $this->autoriser($stock);
        $stock->load(['product', 'predictions', 'adjustments.user', 'orders.buyer', 'dernierePrediction']);

        return view('vendeur.stocks.show', ['stock' => $stock]);
    }

    public function ajuster(Request $request, Stock $stock): RedirectResponse
    {
        $this->autoriser($stock);

        if (in_array($stock->statut, ['vendu', 'annule', 'expire', 'rejete'], true)) {
            return back()->with('error', 'Ce stock n’accepte plus de changement de prix.');
        }

        $data = $request->validate([
            'seller_price' => ['required', 'numeric', 'min:10', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:240'],
        ]);

        $ancien = (float) $stock->prix_vendeur;
        $stock->update([
            'prix_vendeur' => $data['seller_price'],
        ]);
        $prixChange = abs($ancien - (float) $stock->prix_vendeur) > 0.001;
        if ($prixChange) {
            $this->alerterPrix($stock);
        }
        $stock->unsetRelation('dernierePrediction');

        PriceAdjustment::create([
            'stock_id' => $stock->id,
            'seller_id' => $request->user()->id,
            'ancien_prix' => $ancien,
            'nouveau_prix' => $data['seller_price'],
            'indication' => $stock->pertinence()['code'] ?? 'dans_la_fourchette',
            'raison' => $data['reason'] ?? null,
            'cree_le' => now(),
        ]);

        return back()->with('success', $prixChange
            ? 'Prix ajusté. Les acheteurs ont été prévenus. L’offre reste en ligne.'
            : 'Le prix est inchangé. L’offre reste en ligne.');
    }

    public function recalculer(Stock $stock, PricePredictor $predictor): RedirectResponse
    {
        $this->autoriser($stock);
        $stock->load('product');
        $stock->recalculer($predictor);

        return back()->with('success', 'Le prix recommandé a été recalculé selon la durée restante.');
    }

    private function validated(Request $request, bool $photoObligatoire): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:1', 'max:100000'],
            'quartier' => ['required', Rule::in(Lieux::noms())],
            'harvested_on' => ['required', 'date', 'before_or_equal:today'],
            'seller_price' => ['required', 'numeric', 'min:10', 'max:1000000'],
            'min_price' => ['required', 'numeric', 'min:0', 'lte:seller_price'],
            'pickup_mode' => ['required', Rule::in(['sur_place', 'point_rendez_vous'])],
            'photo' => [$photoObligatoire ? 'required' : 'nullable', 'image', 'max:3072'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'min_price.lte' => 'Le prix minimum doit rester inférieur ou égal au prix souhaité.',
            'photo.required' => 'La photo actuelle du produit est nécessaire pour l’analyse.',
        ]);
    }

    private function alerterPrix(Stock $stock): void
    {
        $stock->loadMissing('product');

        if (! in_array($stock->statut, [Stock::PUBLIE, 'partiellement_reserve'], true)) {
            return;
        }

        $this->prevenirAcheteurs(
            $stock,
            'changement_prix',
            $stock->product->nom.' à '.$stock->quartier.' : le prix vient de changer, il est maintenant de '.fcfa($stock->prix_vendeur).'/'.$stock->unite.'.'
        );
    }

    private function prevenirAcheteurs(Stock $stock, string $type, string $message): void
    {
        $lignes = User::query()
            ->where('role', 'acheteur')
            ->pluck('id')
            ->map(fn ($id) => [
                'user_id' => $id,
                'stock_id' => $stock->id,
                'type' => $type,
                'message' => $message,
                'lu' => false,
                'created_at' => now(),
            ])
            ->all();

        if ($lignes !== []) {
            MarketNotification::insert($lignes);
        }
    }

    private function autoriser(Stock $stock): void
    {
        abort_unless($stock->seller_id === auth()->id(), 403);
    }

    public function demanderVerification(Request $request): RedirectResponse
    {
        $request->user()->demanderBadge();

        return back()->with('success', 'Demande d’identité envoyée. Vos stocks restent publiables.');
    }

    public function retirer(Stock $stock): RedirectResponse
    {
        $this->autoriser($stock);

        if ((float) $stock->quantite_bloquee > 0) {
            return back()->with('error', 'Une quantité est déjà réservée. Attendez la collecte avant de retirer ce produit.');
        }

        $stock->update(['statut' => 'annule']);

        return back()->with('success', $stock->product->nom.' a été retiré de la vente.');
    }
}
