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
        Schema::table('reserve_items', function (Blueprint $table) {
            $table->foreign(['product_id'], 'fk_reserve_items_product')->references(['id'])->on('productes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['reserve_id'], 'fk_reserve_items_reserve')->references(['id'])->on('reserves')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserve_items', function (Blueprint $table) {
            $table->dropForeign('fk_reserve_items_product');
            $table->dropForeign('fk_reserve_items_reserve');
        });
    }
};
