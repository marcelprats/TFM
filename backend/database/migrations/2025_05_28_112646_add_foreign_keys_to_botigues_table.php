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
        Schema::table('botigues', function (Blueprint $table) {
            $table->foreign(['vendor_id'])->references(['id'])->on('vendors')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('botigues', function (Blueprint $table) {
            $table->dropForeign('botigues_vendor_id_foreign');
        });
    }
};
