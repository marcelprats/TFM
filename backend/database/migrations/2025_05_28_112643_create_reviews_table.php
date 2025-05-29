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
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_reviews_user');
            $table->unsignedBigInteger('reserve_item_id')->index('idx_reviews_reserve_item');
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['user_id', 'reserve_item_id'], 'uq_reviews_user_reserve_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
