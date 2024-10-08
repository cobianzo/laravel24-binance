<?php
/**
 * Instead of creating a migration for every extra column in the
 * table Users, I create a single migration with all the new columns.
 */

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
        Schema::table('users', function (Blueprint $table) {
            // We update in Profile page
            $table->string('binance_public_key')->nullable();
            $table->string('binance_secret_key')->nullable();

            // We manage in Currencies page
            $table->json('fav_tickers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('binance_public_key');
            $table->dropColumn('binance_secret_key');
            $table->dropColumn('fav_tickers');
        });
    }
};
