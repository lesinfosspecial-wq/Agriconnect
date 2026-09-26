<?php

namespace App\Support;

class Lieux
{
    /**
     * Quartiers utilisés pour la démo à Lomé, plus un point hors rayon.
     *
     * @return array<string, array{0: float, 1: float}>
     */
    public static function tous(): array
    {
        return [
            'Adidogomé' => [6.1862, 1.1526],
            'Agoè' => [6.2315, 1.1092],
            'Tokoin' => [6.1495, 1.1940],
            'Bè' => [6.1365, 1.2485],
            'Djidjolé' => [6.1705, 1.1875],
            'Hédzranawoé' => [6.1728, 1.2204],
            'Kégué' => [6.1560, 1.2890],
            'Baguida' => [6.1615, 1.3210],
            'Cacaveli' => [6.1980, 1.1780],
            'Hanoukopé' => [6.1400, 1.2200],
            'Kpalimé' => [6.9000, 0.6330],
        ];
    }

    public static function noms(): array
    {
        return array_keys(self::tous());
    }

    public static function coordonnees(string $quartier): ?array
    {
        $coordonnees = self::tous()[$quartier] ?? null;

        if ($coordonnees === null) {
            return null;
        }

        return ['latitude' => $coordonnees[0], 'longitude' => $coordonnees[1]];
    }

    public static function ville(string $quartier): string
    {
        return $quartier === 'Kpalimé' ? 'Kpalimé' : 'Lomé';
    }

    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
