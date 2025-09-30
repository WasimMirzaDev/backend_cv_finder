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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->longText('job');
            $table->string('title');
            $table->string('company');
            $table->boolean('cv_created');
            $table->boolean('interview_practice');
            $table->boolean('applied');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['prep', 'appSent', 'shortListed','1stInterview','2ndInterview','finalInterview','onHold','OfferAcctepted','UnSuccessful'])->default('prep');
            $table->timestamps();
            
            // Index for better query performance
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
