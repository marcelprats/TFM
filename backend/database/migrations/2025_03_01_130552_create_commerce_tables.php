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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('botigues', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('productes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->decimal('preu', 10, 2);
            $table->integer('stock');
            $table->string('imatge')->nullable();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade'); // Cada producte està lligat a un venedor
            $table->timestamps();
        });

        Schema::create('botiga_productes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('botiga_id')->constrained('botigues')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('productes')->onDelete('cascade');
            $table->decimal('preu_personalitzat', 10, 2)->nullable(); // Permet que cada botiga tingui preus diferents
            $table->integer('stock_individual')->nullable(); // Stock independent per cada botiga
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('botiga_productes');
        Schema::dropIfExists('productes');
        Schema::dropIfExists('botigues');
        Schema::dropIfExists('vendors');
    }
};
