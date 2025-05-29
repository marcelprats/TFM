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
        Schema::create('importacions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vendor_id')->index('fk_vendor');
            $table->unsignedBigInteger('botiga_id')->index('fk_botiga');
            $table->string('fitxer')->nullable();
            $table->string('raw_file_path')->nullable();
            $table->integer('total_importats')->nullable()->default(0);
            $table->integer('total_errors')->nullable()->default(0);
            $table->integer('records_processed')->nullable()->default(0);
            $table->integer('import_duration')->nullable()->default(0);
            $table->string('status', 50)->nullable()->default('pendents');
            $table->text('processing_notes')->nullable();
            $table->json('errors')->nullable();
            $table->text('observacions')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importacions');
    }
};
