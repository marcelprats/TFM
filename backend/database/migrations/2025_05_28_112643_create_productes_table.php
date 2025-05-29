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
        Schema::create('productes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->integer('categoria')->nullable();
            $table->integer('subcategoria')->nullable();
            $table->decimal('preu', 10);
            $table->integer('stock');
            $table->string('imatge')->nullable();
            $table->unsignedBigInteger('vendor_id')->index('productes_vendor_id_foreign');
            $table->unsignedBigInteger('importacio_id')->nullable()->index('fk_importacio');
            $table->timestamps();
            $table->unsignedBigInteger('botiga_id')->nullable()->index('fk_productes_botiga_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productes');
    }
};
