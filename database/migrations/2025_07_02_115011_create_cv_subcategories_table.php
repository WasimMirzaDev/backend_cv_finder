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
        Schema::create('cv_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questiontype_id')->constrained('cv_questiontypes')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            
            $table->unique(['questiontype_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cv_subcategories');
    }
};
