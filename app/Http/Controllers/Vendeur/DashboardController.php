<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Stock;
use App\Services\Meteo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Meteo $meteo): View
    {
        $user = auth()->user();

        $stocks = Stock::query()
            ->with(['product', 'dernierePrediction'])
            ->where('seller_id', $user->id)
            ->latest()
            ->get();

        $commandes = Order::query()
            ->with(['stock.product', 'buyer', 'pickup'])
            ->whereHas('stock', fn ($query) => $query->where('seller_id', $user->id))
            ->latest()
            ->get();

        $vendues = $commandes->whereIn('statut', [Order::COLLECTEE, Order::TERMINEE]);

        return view('vendeur.dashboard', [
            'stocks' => $stocks,
            'commandes' => $commandes,
            'actifs' => $stocks->whereIn('statut', [Stock::PUBLIE, 'partiellement_reserve'])->filter(fn (Stock $stock) => $stock->availableQuantity() > 0)->count(),
            'urgents' => $stocks->where('urgence', 'urgent')->whereNotIn('statut', ['vendu', 'annule', 'expire'])->count(),
            'ventes' => $vendues->count(),
            'revenu' => $vendues->sum(fn (Order $order) => $order->quantite * $order->prix_unitaire),
            'meteo' => $meteo->pour($user),
        ]);
    }

    public function produits(Request $request): View
    {
        $stocks = Stock::query()
            ->with('product')
            ->where('seller_id', auth()->id())
            ->latest()
            ->get();

        $famille = function (Stock $stock): string {
            if (in_array($stock->statut, ['reserve', 'partiellement_reserve'], true)) {
                return 'reserves';
            }

            if ($stock->availableQuantity() <= 0 || in_array($stock->statut, ['vendu', 'expire', 'annule'], true)) {
                return 'epuises';
            }

            return 'disponibles';
        };

        $q = trim((string) $request->query('q', ''));
        $filtre = (string) $request->query('filtre', 'tous');
        if (! in_array($filtre, ['tous', 'disponibles', 'reserves', 'epuises'], true)) {
            $filtre = 'tous';
        }

        $liste = $stocks
            ->when($q !== '', function ($collection) use ($q) {
                $terme = mb_strtolower($q);

                return $collection->filter(fn (Stock $stock) => str_contains(mb_strtolower($stock->product->nom), $terme));
            })
            ->when($filtre !== 'tous', fn ($collection) => $collection->filter(fn (Stock $stock) => $famille($stock) === $filtre))
            ->values();

        return view('vendeur.produits', [
            'liste' => $liste,
            'filtre' => $filtre,
            'q' => $q,
            'compteurs' => [
                'tous' => $stocks->count(),
                'disponibles' => $stocks->filter(fn (Stock $stock) => $famille($stock) === 'disponibles')->count(),
                'reserves' => $stocks->filter(fn (Stock $stock) => $famille($stock) === 'reserves')->count(),
                'epuises' => $stocks->filter(fn (Stock $stock) => $famille($stock) === 'epuises')->count(),
            ],
        ]);
    }

    public function commandes(Request $request): View
    {
        $commandes = Order::query()
            ->with(['stock.product', 'buyer', 'pickup'])
            ->where('statut', '!=', Order::TERMINEE)
            ->whereHas('stock', fn ($query) => $query->where('seller_id', auth()->id()))
            ->latest()
            ->get();

        $famille = function (Order $order): string {
            return match ($order->statut) {
                Order::RESERVEE => 'nouvelles',
                Order::EN_PREPARATION => 'preparation',
                Order::EN_COLLECTE => 'livraison',
                Order::COLLECTEE => 'retirees',
                default => 'autres',
            };
        };

        $filtre = (string) $request->query('filtre', 'toutes');
        if (! in_array($filtre, ['toutes', 'nouvelles', 'preparation', 'livraison', 'retirees'], true)) {
            $filtre = 'toutes';
        }

        $liste = $filtre === 'toutes'
            ? $commandes
            : $commandes->filter(fn (Order $order) => $famille($order) === $filtre)->values();

        return view('vendeur.commandes', [
            'liste' => $liste,
            'filtre' => $filtre,
            'compteurs' => [
                'toutes' => $commandes->count(),
                'nouvelles' => $commandes->filter(fn (Order $order) => $famille($order) === 'nouvelles')->count(),
                'preparation' => $commandes->filter(fn (Order $order) => $famille($order) === 'preparation')->count(),
                'livraison' => $commandes->filter(fn (Order $order) => $famille($order) === 'livraison')->count(),
                'retirees' => $commandes->filter(fn (Order $order) => $famille($order) === 'retirees')->count(),
            ],
        ]);
    }

    public function statistiques(Request $request): View|RedirectResponse
    {
        $periode = (string) $request->query('periode', '30');
        if (! in_array($periode, ['7', '30', 'mois', 'annee', 'tout'], true)) {
            $periode = '30';
        }

        $saisie = $request->filled('du') || $request->filled('au');
        if ($saisie && (! $request->filled('du') || ! $request->filled('au'))) {
            return redirect()
                ->route('vendeur.statistiques', ['periode' => $periode])
                ->with('error', 'Indiquez la date de début et la date de fin.');
        }

        if ($saisie) {
            $debut = $request->date('du')->startOfDay();
            $fin = $request->date('au')->endOfDay();
            if ($debut->greaterThan($fin)) {
                [$debut, $fin] = [$fin->copy()->startOfDay(), $debut->copy()->endOfDay()];
            }
            $periode = 'intervalle';
            $libelle = 'Du '.$debut->translatedFormat('d F Y').' au '.$fin->copy()->startOfDay()->translatedFormat('d F Y');
        } else {
            [$debut, $libelle] = match ($periode) {
                '7' => [now()->subDays(6)->startOfDay(), '7 derniers jours'],
                '30' => [now()->subDays(29)->startOfDay(), '30 derniers jours'],
                'mois' => [now()->startOfMonth(), now()->translatedFormat('F Y')],
                'annee' => [now()->startOfYear(), 'Année '.now()->year],
                default => [null, 'Toutes les périodes'],
            };
            $fin = now()->endOfDay();
        }

        $commandes = Order::query()
            ->with(['stock.product', 'buyer', 'pickup'])
            ->whereHas('stock', fn ($query) => $query->where('seller_id', auth()->id()))
            ->latest()
            ->get();

        $dateVente = fn (Order $order) => ($order->created_at ?? $order->reservee_jusqu_au)?->copy();
        $ventes = $commandes
            ->where('statut', Order::TERMINEE)
            ->filter(function (Order $order) use ($debut, $fin, $dateVente) {
                $date = $dateVente($order);
                if (! $date) {
                    return false;
                }
                if ($debut && $date->lt($debut)) {
                    return false;
                }

                return $date->lte($fin);
            })
            ->values();
        $montant = fn (Order $order) => (float) $order->quantite * (float) $order->prix_unitaire;

        $parProduit = $ventes
            ->groupBy(fn (Order $order) => $order->stock->product->nom)
            ->map(function ($groupe, $nom) use ($montant) {
                return [
                    'nom' => $nom,
                    'qte' => (float) $groupe->sum('quantite'),
                    'montant' => (float) $groupe->sum($montant),
                    'n' => $groupe->count(),
                ];
            })
            ->sortByDesc('montant')
            ->values();

        $total = (float) $ventes->sum($montant);
        $jours = $this->barresRapport($ventes, $montant, $debut, $fin, $dateVente);

        return view('vendeur.statistiques', [
            'periode' => $periode,
            'libelle' => $libelle,
            'du' => $debut?->toDateString() ?? '',
            'au' => $fin->copy()->startOfDay()->toDateString(),
            'periodes' => [
                '7' => '7 jours',
                '30' => '30 jours',
                'mois' => 'Ce mois',
                'annee' => 'Cette année',
                'tout' => 'Tout',
            ],
            'ventes' => $ventes,
            'total' => $total,
            'kg' => (float) $ventes->sum('quantite'),
            'surPlace' => $ventes->where('canal', 'sur_place')->count(),
            'appli' => $ventes->count() - $ventes->where('canal', 'sur_place')->count(),
            'enCours' => $commandes->whereNotIn('statut', [Order::TERMINEE, Order::ANNULEE])->count(),
            'parProduit' => $parProduit,
            'recentes' => $ventes,
            'jours' => $jours,
            'maxJour' => max(1, (float) collect($jours)->max('montant')),
        ]);
    }

    private function barresRapport($ventes, callable $montant, $debut, $fin, callable $dateVente): array
    {
        $finJour = $fin->copy()->startOfDay();
        $depart = ($debut ?? $ventes->map($dateVente)->filter()->min() ?? now()->startOfMonth())->copy()->startOfDay();
        $parMois = $depart->diffInDays($finJour) > 45;
        $barres = [];

        if ($parMois) {
            $curseur = $depart->copy()->startOfMonth();
            while ($curseur->lessThanOrEqualTo($finJour)) {
                $cle = $curseur->format('Y-m');
                $duMois = $ventes->filter(fn (Order $order) => $dateVente($order)?->format('Y-m') === $cle);
                $barres[] = [
                    'label' => $curseur->translatedFormat('M Y'),
                    'montant' => (float) $duMois->sum($montant),
                ];
                $curseur->addMonth();
            }

            return $barres;
        }

        $curseur = $depart->copy();
        while ($curseur->lessThanOrEqualTo($finJour)) {
            $duJour = $ventes->filter(fn (Order $order) => $dateVente($order)?->isSameDay($curseur));
            $barres[] = [
                'label' => $curseur->translatedFormat('d M'),
                'montant' => (float) $duJour->sum($montant),
            ];
            $curseur->addDay();
        }

        return $barres;
    }
}
