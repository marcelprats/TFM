<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('botiga_productes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('botiga_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('stock');
            $table->timestamps();

            $table->foreign('botiga_id')->references('id')->on('botigues')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('botiga_productes');
    }
};

