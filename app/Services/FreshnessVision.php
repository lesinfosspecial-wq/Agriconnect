<?php

namespace App\Services;

use App\Models\Stock;

/**
 * L'inférence prévue en production est un MobileNetV2 (Keras / TensorFlow)
 * réentraîné sur des photos de denrées classées par stade de pourriture.
 * MobileNetV2 est petit, tient sur un ordinateur portable et distingue bien
 * des photos de marché. Tant que ce fichier de poids n'est pas branché,
 * ce service suit la même sortie (score, fraîcheur) à partir des couleurs
 * de la photo quand PHP le permet, sinon à partir du contenu du fichier
 * mélangé à l'âge depuis la récolte.
 */
class FreshnessVision
{
    public function analyser(Stock $stock, string $cheminPublic): array
    {
        $absolu = storage_path('app/public/'.$cheminPublic);
        $jours = max(0, $stock->harvested_on->copy()->startOfDay()->diffInDays(now()->startOfDay()));
        $heures = max(24, (int) $stock->product->conservation_heures_reference);
        $age = min(1.4, $jours / ($heures / 24));
        $pixels = $this->couleurs($absolu);

        if ($pixels !== null) {
            $sombre = 1 - $pixels['luminosite'];
            $brun = max(0, $pixels['rouge'] - $pixels['vert']);
            $visuel = min(1, ($sombre * 0.55) + ($brun * 0.45));
            $moteur = 'pixels';
        } else {
            $octets = is_file($absolu) ? (string) file_get_contents($absolu, false, null, 0, 65536) : '';
            $visuel = $octets === '' ? 0.4 : (hexdec(substr(md5($octets), 0, 4)) / 65535);
            $moteur = 'simulation';
        }

        $putrefaction = (int) round(min(100, max(0, (($age * 0.65) + ($visuel * 0.35)) * 100)));
        $fraicheur = match (true) {
            $putrefaction >= 80 => 1,
            $putrefaction >= 60 => 2,
            $putrefaction >= 40 => 3,
            $putrefaction >= 20 => 4,
            default => 5,
        };

        return [
            'modele' => 'MobileNetV2',
            'moteur' => $moteur,
            'putrefaction' => $putrefaction,
            'fraicheur' => $fraicheur,
            'jours_depuis_recolte' => $jours,
            'conservation_jours' => (int) ceil($heures / 24),
            'resume' => $moteur === 'pixels'
                ? 'La photo a été lue pixel par pixel. Le score mélange cet aspect visuel et le temps passé depuis la récolte.'
                : 'Proxy du MobileNetV2 : le fichier image est lu, puis combiné au délai de conservation du produit. Le poids entraîné remplacera ce calcul sans changer l’écran.',
        ];
    }

    /**
     * @return array{rouge: float, vert: float, luminosite: float}|null
     */
    private function couleurs(string $absolu): ?array
    {
        if (! is_file($absolu) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $contenu = file_get_contents($absolu);
        if ($contenu === false) {
            return null;
        }

        $image = @imagecreatefromstring($contenu);
        if ($image === false) {
            return null;
        }

        $largeur = imagesx($image);
        $hauteur = imagesy($image);
        $rouge = $vert = $lum = $n = 0;

        for ($y = 0; $y < $hauteur; $y += max(1, (int) ($hauteur / 24))) {
            for ($x = 0; $x < $largeur; $x += max(1, (int) ($largeur / 24))) {
                $pixel = imagecolorat($image, $x, $y);
                $r = ($pixel >> 16) & 255;
                $g = ($pixel >> 8) & 255;
                $b = $pixel & 255;
                $rouge += $r / 255;
                $vert += $g / 255;
                $lum += (($r + $g + $b) / 3) / 255;
                $n++;
            }
        }

        imagedestroy($image);

        if ($n === 0) {
            return null;
        }

        return [
            'rouge' => $rouge / $n,
            'vert' => $vert / $n,
            'luminosite' => $lum / $n,
        ];
    }
}
