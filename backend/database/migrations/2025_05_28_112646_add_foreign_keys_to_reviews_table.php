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
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign(['reserve_item_id'], 'fk_reviews_reserve_item')->references(['id'])->on('reserve_items')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_reviews_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign('fk_reviews_reserve_item');
            $table->dropForeign('fk_reviews_user');
        });
    }
};
