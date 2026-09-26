<?php

namespace App\Support;

class Urgence
{
    public const NORMAL = 'normal';

    public const A_ECOULER = 'a_ecouler';

    public const URGENT = 'urgent';

    public static function classify(int $daysLeft, int $freshness): string
    {
        if ($daysLeft <= 1 || $freshness <= 2) {
            return self::URGENT;
        }

        if ($daysLeft <= 3 || $freshness === 3) {
            return self::A_ECOULER;
        }

        return self::NORMAL;
    }

    public static function poids(string $urgence): int
    {
        return match ($urgence) {
            self::URGENT => 2,
            self::A_ECOULER => 1,
            default => 0,
        };
    }

    public static function priorite(string $urgence): int
    {
        return match ($urgence) {
            self::URGENT => 0,
            self::A_ECOULER => 1,
            default => 2,
        };
    }
}
