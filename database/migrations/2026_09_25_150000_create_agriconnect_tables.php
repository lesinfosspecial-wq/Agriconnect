<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nom', 120);
            $table->string('categorie', 80);
            $table->string('unite', 20);
            $table->integer('conservation_heures_reference');
        });

        Schema::create('price_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('freshness');
            $table->unsignedSmallInteger('days_left');
            $table->string('urgency', 20);
            $table->decimal('demand', 8, 3);
            $table->decimal('supply', 8, 3);
            $table->decimal('quantity', 12, 2);
            $table->decimal('price', 12, 2);
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('seller_id');
            $table->decimal('quantite', 12, 2);
            $table->decimal('quantite_disponible', 12, 2);
            $table->decimal('quantite_bloquee', 12, 2)->default(0);
            $table->string('unite', 20);
            $table->decimal('prix_souhaite', 12, 2);
            $table->decimal('prix_vendeur', 12, 2)->nullable();
            $table->decimal('prix_minimum', 12, 2);
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->string('quartier', 120)->nullable();
            $table->string('ville', 120)->nullable();
            $table->dateTime('recolte_le');
            $table->dateTime('expiration_estimee');
            $table->unsignedTinyInteger('fraicheur')->nullable();
            $table->string('saison', 40)->nullable();
            $table->unsignedTinyInteger('mois')->nullable();
            $table->enum('urgence', ['normal', 'a_ecouler', 'urgent']);
            $table->string('photo_url', 500)->nullable();
            $table->enum('mode_collecte', ['sur_place', 'point_rendez_vous']);
            $table->enum('statut', [
                'brouillon', 'soumis', 'a_modifier', 'rejete', 'publie',
                'partiellement_reserve', 'reserve', 'collecte', 'vendu', 'expire', 'annule', 'suspendu',
            ])->default('brouillon');
            $table->timestamp('created_at')->useCurrent();
            $table->index('seller_id');
            $table->index('statut');
            $table->index('urgence');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('seller_id')->references('id')->on('users');
        });

        Schema::create('price_predictions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('stock_id');
            $table->decimal('prix_recommande', 12, 2);
            $table->decimal('prix_min', 12, 2);
            $table->decimal('prix_max', 12, 2);
            $table->string('saison', 40)->nullable();
            $table->decimal('demande', 12, 2)->nullable();
            $table->decimal('offre', 12, 2)->nullable();
            $table->json('facteurs');
            $table->dateTime('cree_le');
            $table->foreign('stock_id')->references('id')->on('stocks');
        });

        Schema::create('price_adjustments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('stock_id');
            $table->unsignedInteger('seller_id');
            $table->decimal('ancien_prix', 12, 2);
            $table->decimal('nouveau_prix', 12, 2);
            $table->enum('indication', [
                'dans_la_fourchette', 'legerement_superieur', 'fortement_superieur', 'inferieur',
            ]);
            $table->string('raison', 255)->nullable();
            $table->dateTime('cree_le');
            $table->foreign('stock_id')->references('id')->on('stocks');
            $table->foreign('seller_id')->references('id')->on('users');
        });

        Schema::create('identity_validations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedInteger('admin_id');
            $table->dateTime('cree_le');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('admin_id')->references('id')->on('users');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('buyer_id')->nullable();
            $table->unsignedInteger('stock_id');
            $table->decimal('quantite', 12, 2);
            $table->decimal('prix_unitaire', 12, 2);
            $table->enum('statut', [
                'reservee', 'en_preparation', 'en_collecte', 'collectee', 'terminee', 'annulee', 'expiree',
            ]);
            $table->string('canal', 20)->default('appli');
            $table->string('nom_client', 120)->nullable();
            $table->dateTime('reservee_jusqu_au');
            $table->timestamp('created_at')->useCurrent();
            $table->index('buyer_id');
            $table->index('stock_id');
            $table->foreign('buyer_id')->references('id')->on('users');
            $table->foreign('stock_id')->references('id')->on('stocks');
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('auteur_id');
            $table->unsignedInteger('cible_id');
            $table->unsignedInteger('order_id')->unique();
            $table->unsignedTinyInteger('note');
            $table->string('commentaire', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('auteur_id')->references('id')->on('users');
            $table->foreign('cible_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
        });

        Schema::create('pickups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id')->unique();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->string('quartier', 120)->nullable();
            $table->decimal('distance_km', 8, 2);
            $table->integer('duree_minutes');
            $table->json('trace');
            $table->dateTime('heure_collecte');
            $table->enum('statut', ['reservee', 'en_preparation', 'en_collecte', 'collectee', 'terminee']);
            $table->foreign('order_id')->references('id')->on('orders');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('stock_id')->nullable();
            $table->string('type', 40);
            $table->string('message', 255);
            $table->boolean('lu')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('stock_id')->references('id')->on('stocks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('pickups');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('identity_validations');
        Schema::dropIfExists('price_adjustments');
        Schema::dropIfExists('price_predictions');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('price_histories');
        Schema::dropIfExists('products');
    }
};
