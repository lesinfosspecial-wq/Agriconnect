<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE stocks MODIFY statut ENUM(
            'brouillon', 'soumis', 'a_modifier', 'rejete', 'publie',
            'partiellement_reserve', 'reserve', 'collecte', 'vendu', 'expire', 'annule', 'suspendu'
        ) NOT NULL DEFAULT 'brouillon'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE stocks SET statut = 'publie' WHERE statut = 'suspendu'");
        DB::statement("ALTER TABLE stocks MODIFY statut ENUM(
            'brouillon', 'soumis', 'a_modifier', 'rejete', 'publie',
            'partiellement_reserve', 'reserve', 'collecte', 'vendu', 'expire', 'annule'
        ) NOT NULL DEFAULT 'brouillon'");
    }
};
