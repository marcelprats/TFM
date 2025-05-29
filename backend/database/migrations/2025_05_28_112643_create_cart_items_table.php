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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cart_id')->index('fk_cart_items_cart');
            $table->unsignedBigInteger('product_id')->index('fk_cart_items_product');
            $table->integer('quantity')->default(1);
            $table->decimal('reserved_price', 10);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->boolean('selected')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
