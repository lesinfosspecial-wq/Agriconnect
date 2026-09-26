<?php

if (! function_exists('fcfa')) {
    function fcfa(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 0, ',', ' ').' FCFA';
    }
}

if (! function_exists('km')) {
    function km(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $km = (float) $value;

        return number_format($km, $km < 10 ? 1 : 0, ',', ' ').' km';
    }
}
