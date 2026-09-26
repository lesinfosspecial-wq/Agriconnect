<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use App\Models\Favori;
use App\Models\Stock;
use App\Services\MatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriController extends Controller
{
    public function index(MatchingService $matching): View
    {
        $ids = Favori::idsPour(auth()->id());
        $offres = Stock::query()
            ->with(['product', 'seller'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function (Stock $stock) use ($matching) {
                $stock->distance_km = $matching->distance(auth()->user(), $stock);

                return $stock;
            });

        return view('acheteur.favoris', [
            'offres' => $offres,
            'favoris' => $ids,
        ]);
    }

    public function toggle(Stock $stock): RedirectResponse
    {
        $favori = Favori::query()
            ->where('user_id', auth()->id())
            ->where('stock_id', $stock->id)
            ->first();

        if ($favori) {
            $favori->delete();

            return back()->with('success', $stock->product->nom.' a été retiré des favoris.');
        }

        Favori::create([
            'user_id' => auth()->id(),
            'stock_id' => $stock->id,
        ]);

        return back()->with('success', $stock->product->nom.' a été ajouté aux favoris.');
    }
}
