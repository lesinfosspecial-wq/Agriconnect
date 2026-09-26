<?php

namespace App\Services;

use App\Models\User;
use App\Support\Lieux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Meteo
{
    public function pour(User $user): ?array
    {
        $latitude = $user->latitude;
        $longitude = $user->longitude;

        if ($latitude === null || $longitude === null) {
            $lieu = Lieux::coordonnees((string) $user->quartier);
            $latitude = $lieu['latitude'] ?? null;
            $longitude = $lieu['longitude'] ?? null;
        }

        if ($latitude === null || $longitude === null) {
            return null;
        }

        $cle = sprintf('meteo.%s.%s', round((float) $latitude, 2), round((float) $longitude, 2));

        $releve = Cache::remember($cle, now()->addMinutes(30), function () use ($latitude, $longitude) {
            try {
                $reponse = Http::timeout(4)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,weather_code',
                    'timezone' => 'Africa/Lome',
                ]);
            } catch (\Throwable) {
                return null;
            }

            if (! $reponse->ok()) {
                return null;
            }

            $courant = $reponse->json('current');

            if (! is_array($courant) || ! isset($courant['temperature_2m'])) {
                return null;
            }

            return [
                'temperature' => (int) round((float) $courant['temperature_2m']),
                'code' => (int) ($courant['weather_code'] ?? 0),
            ];
        });

        if (! is_array($releve)) {
            return null;
        }

        return [
            'lieu' => $user->quartier ?: ($user->ville ?: 'Lomé'),
            'temperature' => $releve['temperature'],
            'texte' => $this->libelle($releve['code']),
            'icone' => $this->icone($releve['code']),
        ];
    }

    private function libelle(int $code): string
    {
        return match (true) {
            $code === 0 => 'Ciel dégagé',
            $code <= 3 => 'Peu nuageux',
            $code <= 48 => 'Brume',
            $code <= 67 => 'Pluie',
            $code <= 77 => 'Averses',
            $code <= 82 => 'Averses',
            default => 'Orage',
        };
    }

    private function icone(int $code): string
    {
        return match (true) {
            $code === 0 => 'bi-sun-fill',
            $code <= 3 => 'bi-cloud-sun-fill',
            $code <= 48 => 'bi-cloud-fill',
            $code <= 82 => 'bi-cloud-rain-fill',
            default => 'bi-cloud-lightning-rain-fill',
        };
    }
}
