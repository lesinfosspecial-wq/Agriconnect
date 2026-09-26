<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EweVoix
{
    public function transcrire(string $cheminWav): string
    {
        set_time_limit(0);
        $this->assurer();

        $reponse = Http::connectTimeout(5)->timeout(900)
            ->attach('audio', file_get_contents($cheminWav), 'note.wav')
            ->post($this->url().'/transcrire');

        if (! $reponse->successful()) {
            $detail = $reponse->json('erreur') ?: $reponse->body();
            throw new RuntimeException('La transcription éwé a échoué. '.$detail);
        }

        return trim((string) $reponse->json('texte'));
    }

    public function parler(string $texte): string
    {
        $this->assurer();

        $reponse = Http::connectTimeout(5)->timeout(900)
            ->accept('audio/wav')
            ->post($this->url().'/parler', ['texte' => $texte]);

        if (! $reponse->successful() || $reponse->body() === '') {
            throw new RuntimeException('La lecture éwé a échoué.');
        }

        $nom = 'voix/'.uniqid('note_', true).'.wav';
        \Illuminate\Support\Facades\Storage::disk('public')->put($nom, $reponse->body());

        return $nom;
    }

    private function assurer(): void
    {
        if ($this->disponible()) {
            return;
        }

        $occupe = @fsockopen('127.0.0.1', (int) (parse_url($this->url(), PHP_URL_PORT) ?: 8765), $errno, $err, 0.3);
        if (is_resource($occupe)) {
            fclose($occupe);
            throw new RuntimeException('Le service de voix est déjà ouvert mais ne répond plus. Fermez sa fenêtre avec CTRL+C, puis relancez-la une seule fois.');
        }

        $python = base_path('python/ewe_voix/.venv/Scripts/python.exe');
        if (! is_file($python)) {
            $python = 'python';
        }
        $script = base_path('python/ewe_voix/serveur.py');
        pclose(popen('cmd /C start /B "" "'.$python.'" "'.$script.'"', 'r'));

        for ($i = 0; $i < 40; $i++) {
            usleep(300000);
            if ($this->disponible()) {
                return;
            }
        }

        throw new RuntimeException('Le service de voix éwé ne répond pas. Installez les bibliothèques puis relancez python/ewe_voix/serveur.py.');
    }

    private function disponible(): bool
    {
        try {
            return Http::timeout(2)->get($this->url().'/sante')->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function url(): string
    {
        return rtrim((string) env('EWE_VOIX_URL', 'http://127.0.0.1:8765'), '/');
    }
}
