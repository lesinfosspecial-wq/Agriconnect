<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AssistantProducteur;
use App\Services\EweVoix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TraiterNoteEwe extends Command
{
    protected $signature = 'ewe:note {user}';

    protected $description = 'Transcrit une note éwé et prépare la réponse vocale, sans bloquer le site.';

    public function handle(AssistantProducteur $assistant, EweVoix $voix): int
    {
        $cle = 'ewe-job.'.$this->argument('user');
        $travail = Cache::get($cle);
        $utilisateur = User::find($this->argument('user'));

        if (! is_array($travail) || ! $utilisateur) {
            return self::SUCCESS;
        }

        try {
            $entendu = $voix->transcrire(Storage::disk('public')->path($travail['audio']));
            if ($entendu === '') {
                $entendu = 'nyemese egome o';
            }

            $etat = $assistant->repondre($utilisateur, $entendu, $travail['etat']);
            $messages = $etat['messages'];
            $fin = count($messages) - 1;
            $note = $voix->parler((string) $messages[$fin]['texte']);
            $messages[$fin]['vocal'] = true;
            $messages[$fin]['audio'] = $note;
            if ($fin > 0) {
                $messages[$fin - 1]['vocal'] = true;
                $messages[$fin - 1]['audio'] = $travail['audio'];
            }
            $etat['messages'] = $messages;
            $travail['statut'] = 'pret';
            $travail['etat'] = $etat;
        } catch (\Throwable $e) {
            $travail['statut'] = 'erreur';
            $travail['message'] = $e->getMessage();
        }

        if (Cache::get($cle) === null) {
            return self::SUCCESS;
        }

        Cache::put($cle, $travail, now()->addMinutes(30));

        return self::SUCCESS;
    }
}
