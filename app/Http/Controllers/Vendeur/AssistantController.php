<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PriceAdjustment;
use App\Models\Stock;
use App\Models\User;
use App\Services\AssistantProducteur;
use App\Services\EweVoix;
use App\Services\PricePredictor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AssistantController extends Controller
{
    public function index(Request $request, AssistantProducteur $assistant): View|RedirectResponse
    {
        $etat = $this->appliquerVoix($request->user()->id, session('assistant_producteur', $assistant->etatInitial($request->user())));
        $intention = (string) $request->query('intention', '');

        if ($request->query('ecran') === 'accueil') {
            $etat['mode'] = null;
            $etat['pret'] = false;
            session(['assistant_producteur' => $etat]);

            return redirect()->route('vendeur.assistant');
        }

        if (in_array($intention, ['ajouter', 'discuter', 'ewe'], true)) {
            $etat = $assistant->ouvrir($etat, $intention, $request->user());
            session(['assistant_producteur' => $etat]);

            return redirect()->route('vendeur.assistant');
        }

        $travail = Cache::get($this->cleVoix($request->user()->id));
        if (is_array($travail) && ($travail['statut'] ?? null) === 'en_cours' && ($etat['mode'] ?? null) === null) {
            $etat['mode'] = 'ewe';
        }

        session(['assistant_producteur' => $etat]);

        return view('vendeur.assistant', [
            'etat' => $etat,
            'accueil' => $this->accueil($request->user()),
            'enCours' => is_array($travail) && ($travail['statut'] ?? null) === 'en_cours',
        ]);
    }

    public function message(Request $request, AssistantProducteur $assistant): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $etat = session('assistant_producteur', $assistant->etatInitial($request->user()));
        $etat = $assistant->repondre($request->user(), $data['message'], $etat);
        session(['assistant_producteur' => $etat]);

        return redirect()->route('vendeur.assistant');
    }

    public function vocal(Request $request, AssistantProducteur $assistant, EweVoix $voix): RedirectResponse
    {
        $etat = session('assistant_producteur', $assistant->etatInitial($request->user()));
        if (($etat['mode'] ?? null) !== 'ewe') {
            return back()->with('error', 'La note vocale est réservée à la conversation en éwé.');
        }

        $request->validate([
            'audio' => ['required', 'file', 'max:8192'],
        ]);

        set_time_limit(30);

        $cle = $this->cleVoix($request->user()->id);
        $enCours = Cache::get($cle);
        if (is_array($enCours) && ($enCours['statut'] ?? null) === 'en_cours') {
            return back()->with('error', 'Une note est déjà en cours. Vous pouvez changer de menu, la réponse arrivera dans l’assistant.');
        }

        try {
            $chemin = $request->file('audio')->store('voix', 'public');
            Cache::put($cle, [
                'statut' => 'en_cours',
                'audio' => $chemin,
                'etat' => $etat,
            ], now()->addMinutes(30));
            $etat['messages'][] = [
                'de' => 'producteur',
                'texte' => '',
                'heure' => now()->format('H:i'),
                'vocal' => true,
                'audio' => $chemin,
            ];
            session(['assistant_producteur' => $etat]);

            if (app()->runningUnitTests()) {
                Artisan::call('ewe:note', ['user' => $request->user()->id]);
            } else {
                pclose(popen('cmd /C start /B "" "'.PHP_BINARY.'" "'.base_path('artisan').'" ewe:note '.$request->user()->id, 'r'));
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'La note n’a pas pu partir. '.$e->getMessage());
        }

        return redirect()->route('vendeur.assistant');
    }

    public function voixEtat(Request $request): JsonResponse
    {
        $travail = Cache::get($this->cleVoix($request->user()->id));

        return response()->json(['statut' => $travail['statut'] ?? 'aucun']);
    }

    public function vider(): RedirectResponse
    {
        session()->forget('assistant_producteur');
        Cache::forget($this->cleVoix((int) auth()->id()));

        return redirect()->route('vendeur.assistant')->with('success', 'Conversation effacée.');
    }

    private function cleVoix(int $userId): string
    {
        return 'ewe-job.'.$userId;
    }

    private function appliquerVoix(int $userId, array $etat): array
    {
        $travail = Cache::get($this->cleVoix($userId));
        if (! is_array($travail)) {
            return $etat;
        }

        if (($travail['statut'] ?? null) === 'pret' && isset($travail['etat'])) {
            Cache::forget($this->cleVoix($userId));

            return $travail['etat'];
        }

        if (($travail['statut'] ?? null) === 'erreur') {
            Cache::forget($this->cleVoix($userId));
            session()->flash('error', 'La voix n’a pas pu répondre. '.$travail['message']);
        }

        return $etat;
    }

    private function accueil(User $producteur): array
    {
        $stocks = Stock::query()->with(['product', 'predictions'])->where('seller_id', $producteur->id)->get();
        $commandes = Order::query()->with('stock.product')->whereHas('stock', fn ($q) => $q->where('seller_id', $producteur->id))->get();
        $debut = now()->subDays(7);
        $ventes = $commandes->where('statut', Order::TERMINEE);
        $enLigne = $stocks->filter(fn (Stock $stock) => in_array($stock->statut, [Stock::PUBLIE, 'partiellement_reserve'], true) && $stock->availableQuantity() > 0);
        $priorite = $enLigne->sortBy(fn (Stock $stock) => $stock->joursRestants())->first();
        $produit = $priorite?->product?->nom ?? $stocks->first()?->product?->nom ?? 'tomates';

        $conseil = 'Publiez une offre ici : les acheteurs sont prévenus dès que vous confirmez la fiche.';
        if ($priorite) {
            $conseil = $priorite->product->nom.' à '.$priorite->quartier.' : '.$priorite->joursRestants().' jour(s) avant la limite, '.fcfa($priorite->seller_price).' / '.$priorite->unite.'.';
            $prediction = app(PricePredictor::class)->estimate($priorite->product, [
                'days_left' => $priorite->joursRestants(),
                'freshness' => (int) ($priorite->freshness ?: 5),
                'quantity' => (float) $priorite->availableQuantity(),
            ]);
            if ((float) $priorite->seller_price > (float) $prediction['max']) {
                $conseil .= ' Le prix est au-dessus de la fourchette ('.fcfa($prediction['recommended']).').';
            }
        }

        $semaine = fn ($collection, string $champ = 'created_at') => $collection->filter(function ($ligne) use ($debut, $champ) {
            $date = $ligne->{$champ} ?? null;

            return $date && $date->greaterThanOrEqualTo($debut);
        });

        return [
            'prenom' => strtok((string) $producteur->nom, ' ') ?: 'producteur',
            'produit' => mb_strtolower($produit),
            'conseil' => $conseil,
            'stock' => $priorite,
            'stats' => [
                ['icone' => 'bi-flower1', 'libelle' => 'Produits analysés', 'valeur' => (string) $stocks->filter(fn (Stock $stock) => $stock->predictions->isNotEmpty())->count(), 'delta' => $semaine($stocks)->count()],
                ['icone' => 'bi-people', 'libelle' => 'Acheteurs trouvés', 'valeur' => (string) $commandes->pluck('buyer_id')->filter()->unique()->count(), 'delta' => $semaine($ventes)->pluck('buyer_id')->filter()->unique()->count()],
                ['icone' => 'bi-shield-check', 'libelle' => 'Prix ajustés', 'valeur' => (string) PriceAdjustment::query()->where('seller_id', $producteur->id)->count(), 'delta' => PriceAdjustment::query()->where('seller_id', $producteur->id)->where('cree_le', '>=', $debut)->count()],
                ['icone' => 'bi-clock', 'libelle' => 'Quantité écoulée', 'valeur' => number_format((float) $ventes->sum('quantite'), 0, ',', ' ').' kg', 'delta' => (int) round((float) $semaine($ventes)->sum('quantite'))],
            ],
        ];
    }
}
