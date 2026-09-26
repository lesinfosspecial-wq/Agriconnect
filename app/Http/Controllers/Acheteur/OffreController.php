<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Services\MatchingService;
use Illuminate\View\View;

class OffreController extends Controller
{
    public function show(Stock $stock, MatchingService $matching): View
    {
        abort_unless($stock->visiblePar(auth()->user()), 404);

        $stock->load(['product', 'seller.notesRecues', 'dernierePrediction']);

        $distance = $matching->distance(auth()->user(), $stock);
        $maps = null;

        if ($stock->latitude && $stock->longitude && auth()->user()->latitude && auth()->user()->longitude) {
            $origine = auth()->user()->latitude.','.auth()->user()->longitude;
            $destination = $stock->latitude.','.$stock->longitude;
            $maps = 'https://www.google.com/maps/dir/?api=1&origin='.urlencode($origine).'&destination='.urlencode($destination);
        }

        return view('acheteur.offres.show', [
            'stock' => $stock,
            'distance' => $distance,
            'maps' => $maps,
            'favori' => \App\Models\Favori::query()->where('user_id', auth()->id())->where('stock_id', $stock->id)->exists(),
        ]);
    }
}
