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
        Schema::table('importacions', function (Blueprint $table) {
            $table->foreign(['botiga_id'], 'fk_botiga')->references(['id'])->on('botigues')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['vendor_id'], 'fk_vendor')->references(['id'])->on('vendors')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importacions', function (Blueprint $table) {
            $table->dropForeign('fk_botiga');
            $table->dropForeign('fk_vendor');
        });
    }
};
