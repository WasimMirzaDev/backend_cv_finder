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
        Schema::table('pdf_parsed', function (Blueprint $table) {
            $table->string('training_file_id')->nullable();
            $table->boolean('used_ocr')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_parsed', function (Blueprint $table) {
            $table->dropColumn('training_file_id');
            $table->dropColumn('used_ocr');
        });
    }
};
