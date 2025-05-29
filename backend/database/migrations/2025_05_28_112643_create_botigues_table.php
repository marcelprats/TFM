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
        Schema::create('botigues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->unsignedBigInteger('vendor_id')->index('botigues_vendor_id_foreign');
            $table->timestamps();
            $table->string('address')->default('');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('botigues');
    }
};
