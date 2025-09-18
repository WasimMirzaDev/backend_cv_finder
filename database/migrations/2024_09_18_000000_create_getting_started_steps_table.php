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
        Schema::create('getting_started_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('sign_up')->default(false);
            $table->boolean('first_cv')->default(false);
            $table->boolean('first_interview')->default(false);
            $table->boolean('progress_tracker')->default(false);
            $table->boolean('applied_job')->default(false);
            $table->boolean('refer_friend')->default(false);
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('getting_started_steps');
    }
};
