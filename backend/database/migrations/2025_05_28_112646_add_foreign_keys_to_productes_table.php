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
        Schema::table('productes', function (Blueprint $table) {
            $table->foreign(['importacio_id'], 'fk_importacio')->references(['id'])->on('importacions')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['botiga_id'], 'fk_productes_botiga_id')->references(['id'])->on('botigues')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['vendor_id'])->references(['id'])->on('vendors')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productes', function (Blueprint $table) {
            $table->dropForeign('fk_importacio');
            $table->dropForeign('fk_productes_botiga_id');
            $table->dropForeign('productes_vendor_id_foreign');
        });
    }
};
