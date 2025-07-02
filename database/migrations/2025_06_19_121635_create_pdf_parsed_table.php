<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pdf_parsed', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('full_name')->nullable()->comment('Extracted from the parsed PDF');
            $table->string('file_name')->nullable();
            $table->json('parsed_data')->nullable()->comment('Full parsed data in JSON format');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('ip_address');
            $table->index('full_name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_parsed');
    }
};
