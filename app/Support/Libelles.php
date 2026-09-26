<?php

namespace App\Support;

class Libelles
{
    public static function role(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrateur',
            'vendeur' => 'Producteur / vendeur',
            'acheteur' => 'Acheteur',
            default => $role,
        };
    }

    public static function statutStock(string $statut): string
    {
        return match ($statut) {
            'brouillon' => 'Brouillon',
            'soumis' => 'Soumis',
            'a_modifier' => 'À modifier',
            'rejete' => 'Refusé',
            'publie' => 'Publié',
            'partiellement_reserve' => 'Partiellement réservé',
            'reserve' => 'Réservé',
            'collecte' => 'En collecte',
            'vendu' => 'Vendu',
            'expire' => 'Expiré',
            'annule' => 'Annulé',
            'suspendu' => 'Suspendu',
            default => $statut,
        };
    }

    public static function urgence(string $urgence): string
    {
        return match ($urgence) {
            'urgent' => 'Urgent',
            'a_ecouler' => 'À écouler',
            default => 'Normal',
        };
    }

    public static function fraicheur(int $niveau): string
    {
        return match ($niveau) {
            1 => 'Très avancée',
            2 => 'Avancée',
            3 => 'Correcte',
            4 => 'Fraîche',
            5 => 'Très fraîche',
            default => 'Non précisée',
        };
    }

    public static function commande(string $statut): string
    {
        return match ($statut) {
            'reservee' => 'Réservé',
            'en_preparation' => 'En préparation',
            'en_collecte' => 'À retirer',
            'collectee' => 'Retirée',
            'terminee' => 'Terminé',
            'annulee' => 'Annulé',
            'expiree' => 'Expiré',
            default => $statut,
        };
    }

    public static function collecte(string $mode): string
    {
        return match ($mode) {
            'point_rendez_vous', 'rdv' => 'Point de rendez-vous',
            default => 'Retrait sur place',
        };
    }

    public static function etapeCommande(string $statut): ?string
    {
        return match ($statut) {
            'reservee' => 'Passer en préparation',
            'en_preparation' => 'Indiquer que c’est prêt à retirer',
            'en_collecte' => 'Confirmer le retrait',
            'collectee' => 'Terminer la commande',
            default => null,
        };
    }

    public static function verification(string $etat): string
    {
        return match ($etat) {
            'verifie' => 'Identité vérifiée',
            'en_cours' => 'Vérification en cours',
            default => 'Identité non vérifiée',
        };
    }

    public static function saison(int $month): string
    {
        return match (true) {
            in_array($month, [12, 1, 2], true) => 'Saison sèche',
            in_array($month, [3, 4, 5, 6], true) => 'Grande saison des pluies',
            in_array($month, [7, 8], true) => 'Petite saison sèche',
            default => 'Petite saison des pluies',
        };
    }

    public static function decision(string $decision): string
    {
        return match ($decision) {
            'accepte' => 'Acceptée',
            'modification' => 'Modification demandée',
            'rejete' => 'Refusée',
            default => $decision,
        };
    }
}
