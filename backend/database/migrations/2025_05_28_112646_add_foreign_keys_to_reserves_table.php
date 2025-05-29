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
        Schema::table('reserves', function (Blueprint $table) {
            $table->foreign(['botiga_id'], 'fk_reserves_botiga')->references(['id'])->on('botigues')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserves', function (Blueprint $table) {
            $table->dropForeign('fk_reserves_botiga');
        });
    }
};
