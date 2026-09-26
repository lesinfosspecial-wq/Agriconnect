<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('role', ['vendeur', 'acheteur', 'admin']);
            $table->string('nom', 120);
            $table->string('telephone', 20)->unique();
            $table->string('mot_de_passe');
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('quartier', 120)->nullable();
            $table->string('ville', 120)->nullable();
            $table->enum('verification', ['non_verifie', 'en_cours', 'verifie'])->default('non_verifie');
            $table->decimal('quantite_recherchee', 12, 2)->nullable();
            $table->decimal('prix_max', 12, 2)->nullable();
            $table->decimal('rayon_km', 8, 2)->nullable();
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
