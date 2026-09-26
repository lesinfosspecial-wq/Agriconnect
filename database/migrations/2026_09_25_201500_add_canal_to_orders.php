<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'canal')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('canal', 20)->default('appli');
                $table->string('nom_client', 120)->nullable();
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY buyer_id INT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'canal')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['canal', 'nom_client']);
            });
        }
    }
};
