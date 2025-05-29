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
        Schema::create('reserves', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('buyer_id')->index('fk_reserves_buyer');
            $table->string('buyer_type', 50);
            $table->unsignedBigInteger('botiga_id')->index('fk_reserves_botiga');
            $table->decimal('total_reserved', 10)->default(0);
            $table->decimal('deposit_amount', 10)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->string('ticket_code', 50)->nullable()->unique('ticket_code');
            $table->text('observations')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserves');
    }
};
