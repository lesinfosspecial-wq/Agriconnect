<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;

class LocalisationController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $request->user()->update([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ]);

        return response()->noContent();
    }
}
