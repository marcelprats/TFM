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
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reserve_id')->index('fk_orders_reserve');
            $table->string('order_number', 50)->unique('order_number');
            $table->unsignedBigInteger('buyer_id');
            $table->decimal('total_amount', 10);
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'reserved', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('completed_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->string('buyer_type', 50)->default('user');
            $table->string('cancellation_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
