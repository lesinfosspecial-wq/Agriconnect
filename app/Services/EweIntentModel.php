<?php

namespace App\Services;

/**
 * Classifieur à centroïdes entraîné sur des phrases éwé du marché.
 * Chaque phrase d'exemple donne des mots ; chaque intention a un centre.
 * Une phrase nouvelle est rangée vers le centre le plus proche.
 * L'éwé seul est couvert pour l'instant : timáti, akɔɖu, atɔtɔ, atádí, mango, sabala.
 */
class EweIntentModel
{
    public function predire(string $brut): array
    {
        $question = $this->sac($brut);
        if ($question === []) {
            return ['intention' => 'inconnu', 'score' => 0.0];
        }

        $meilleur = 'inconnu';
        $score = 0.0;

        foreach ($this->centres() as $intention => $centre) {
            $cosinus = $this->cosinus($question, $centre);
            if ($cosinus > $score) {
                $score = $cosinus;
                $meilleur = $intention;
            }
        }

        if ($score < 0.22) {
            return ['intention' => 'inconnu', 'score' => $score];
        }

        return ['intention' => $meilleur, 'score' => $score];
    }

    public function estOui(string $brut): bool
    {
        return in_array($this->repli($brut), ['e', 'ee', 'eeh', 'eh', 'yoo', 'yo', 'oui'], true);
    }

    public function estNon(string $brut): bool
    {
        return in_array($this->repli($brut), ['ao', 'awo', 'non'], true);
    }

    public function versFrancais(string $brut): string
    {
        $mots = preg_split('/\s+/u', $this->repli($brut)) ?: [];
        $table = [
            'timati' => 'tomates', 'tomati' => 'tomates',
            'akodu' => 'bananes', 'akordu' => 'bananes',
            'atoto' => 'ananas',
            'atadi' => 'piment',
            'sabala' => 'oignon',
            'mango' => 'mangue',
            'kilo' => 'kg', 'kiloo' => 'kg',
            'egbe' => "aujourd'hui",
        ];

        $sortie = array_map(fn (string $mot) => $table[$mot] ?? $mot, $mots);

        return implode(' ', $sortie);
    }

    public function nom(string $francais): string
    {
        return match (mb_strtolower($francais)) {
            'tomate' => 'timáti',
            'banane' => 'akɔɖu',
            'ananas' => 'atɔtɔ',
            'piment' => 'atádí',
            'mangue' => 'mango',
            'oignon' => 'sabala',
            default => $francais,
        };
    }

    public function catalogue(): string
    {
        return 'timáti, akɔɖu, atɔtɔ, atádí, mango, sabala';
    }

    /**
     * @return array<string, array<string, float>>
     */
    private function centres(): array
    {
        $poids = [];
        $taille = [];

        foreach ($this->exemples() as [$phrase, $intention]) {
            $taille[$intention] = ($taille[$intention] ?? 0) + 1;
            foreach (array_unique($this->tokens($phrase)) as $mot) {
                $poids[$intention][$mot] = ($poids[$intention][$mot] ?? 0) + 1;
            }
        }

        $centres = [];
        foreach ($poids as $intention => $sac) {
            foreach ($sac as $mot => $compte) {
                $centres[$intention][$mot] = $compte / $taille[$intention];
            }
        }

        return $centres;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function exemples(): array
    {
        return [
            ['woezo', 'saluer'],
            ['ndi na wo', 'saluer'],
            ['efoa', 'saluer'],
            ['akpe na wo', 'saluer'],
            ['medi be madzra timati', 'ajouter'],
            ['80 kilo timati egbe', 'ajouter'],
            ['madzra akodu le adidogome', 'ajouter'],
            ['atadi 20 kilo', 'ajouter'],
            ['nu si madzra', 'ajouter'],
            ['e', 'confirmer'],
            ['ee yoo', 'confirmer'],
            ['eso', 'confirmer'],
            ['ao', 'refuser'],
            ['awo nyeme lɔ o', 'refuser'],
            ['nenie nye timati', 'prix'],
            ['timati fe ga', 'prix'],
            ['ga nenie', 'prix'],
            ['nenie nye akodu', 'prix'],
            ['ga si mexɔ', 'ventes'],
            ['nye xexe', 'ventes'],
            ['asi si me xɔ', 'ventes'],
            ['nusiwo le asinye', 'stocks'],
            ['nu si medo', 'stocks'],
            ['dzi ŋku nu si madzra', 'stocks'],
            ['amesiwo fle nu', 'commandes'],
            ['nuflela', 'commandes'],
            ['wo le vava', 'commandes'],
            ['yame le aleke', 'meteo'],
            ['tsi dza', 'meteo'],
            ['ndo le te', 'meteo'],
            ['aleke wɔe', 'guide'],
            ['kpe de nye nu', 'guide'],
            ['nyemese egome o', 'guide'],
        ];
    }

    /**
     * @return array<string, float>
     */
    private function sac(string $brut): array
    {
        $sac = [];
        foreach ($this->tokens($brut) as $mot) {
            $sac[$mot] = 1.0;
        }

        return $sac;
    }

    /**
     * @return list<string>
     */
    private function tokens(string $brut): array
    {
        $mots = preg_split('/\s+/u', $this->repli($brut)) ?: [];

        return array_values(array_filter($mots, fn (string $mot) => mb_strlen($mot) >= 2 && ! preg_match('/^\d+$/', $mot)));
    }

    private function repli(string $brut): string
    {
        $brut = mb_strtolower(trim($brut));
        $brut = strtr($brut, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ɛ' => 'e', 'ɔ' => 'o', 'ŋ' => 'n', 'ɖ' => 'd', 'ʋ' => 'v',
            'ƒ' => 'f', 'Ẽ' => 'e', 'ẽ' => 'e', 'ɛ̃' => 'e',
            '’' => ' ', "'" => ' ', '-' => ' ',
        ]);

        return trim((string) preg_replace('/\s+/u', ' ', $brut));
    }

    /**
     * @param  array<string, float>  $a
     * @param  array<string, float>  $b
     */
    private function cosinus(array $a, array $b): float
    {
        $produit = 0.0;
        foreach ($a as $mot => $poids) {
            $produit += $poids * ($b[$mot] ?? 0);
        }
        $gauche = sqrt(array_sum(array_map(fn ($poids) => $poids ** 2, $a)));
        $droite = sqrt(array_sum(array_map(fn ($poids) => $poids ** 2, $b)));

        if ($gauche == 0.0 || $droite == 0.0) {
            return 0.0;
        }

        return $produit / ($gauche * $droite);
    }
}
