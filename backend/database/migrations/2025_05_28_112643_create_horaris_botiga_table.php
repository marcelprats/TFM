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
        Schema::create('horaris_botiga', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('botiga_id')->index('botiga_id');
            $table->enum('dia', ['dilluns', 'dimarts', 'dimecres', 'dijous', 'divendres', 'dissabte', 'diumenge']);
            $table->time('obertura');
            $table->time('tancament');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horaris_botiga');
    }
};
