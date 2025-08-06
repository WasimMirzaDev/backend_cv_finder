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
        Schema::create('cv_questions', function (Blueprint $table) {
            $table->id();
            $table->text('speech');
            $table->string('title');
            $table->string('unique_id')->unique();
            $table->string('avatar')->nullable();
            $table->string('video_id')->nullable();
            $table->string('difficulty_slug');
            $table->string('questiontype_slug');
            $table->string('subcategories_slug');
            $table->unsignedInteger('question_number');
            $table->timestamps();
            
            // Add indexes for better query performance
            $table->index(['difficulty_slug', 'questiontype_slug', 'subcategories_slug', 'question_number'], 'question_identifier_index');
            
            // Add foreign key constraints if needed
            // $table->foreign('difficulty_slug')->references('slug')->on('cv_difficultylevels');
            // $table->foreign('questiontype_slug')->references('slug')->on('cv_questiontypes');
            // Note: Subcategory foreign key would need to be a composite key or reference the full slug
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cv_questions');
    }
};
