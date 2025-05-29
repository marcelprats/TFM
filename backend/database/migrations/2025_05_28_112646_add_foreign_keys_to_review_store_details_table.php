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
        Schema::table('review_store_details', function (Blueprint $table) {
            $table->foreign(['review_id'], 'fk_review')->references(['id'])->on('reviews')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_store_details', function (Blueprint $table) {
            $table->dropForeign('fk_review');
        });
    }
};
