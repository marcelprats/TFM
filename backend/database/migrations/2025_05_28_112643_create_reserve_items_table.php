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
        Schema::create('reserve_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reserve_id')->index('fk_reserve_items_reserve');
            $table->unsignedBigInteger('product_id')->index('fk_reserve_items_product');
            $table->integer('quantity')->default(1);
            $table->decimal('reserved_price', 10);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserve_items');
    }
};
