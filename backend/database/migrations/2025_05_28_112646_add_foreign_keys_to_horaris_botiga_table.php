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
        Schema::table('horaris_botiga', function (Blueprint $table) {
            $table->foreign(['botiga_id'], 'horaris_botiga_ibfk_1')->references(['id'])->on('botigues')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horaris_botiga', function (Blueprint $table) {
            $table->dropForeign('horaris_botiga_ibfk_1');
        });
    }
};
