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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_industry_id')->nullable()->constrained('cv_subcategories')->onDelete('set null');
            $table->foreignId('role_id')->nullable()->constrained('cv_roles')->onDelete('set null');
            $table->foreignId('education_level_id')->nullable()->constrained('cv_education_levels')->onDelete('set null');
            $table->string('linkedin_profile_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['preferred_industry_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['education_level_id']);
            $table->dropColumn([
                'preferred_industry_id',
                'role_id',
                'education_level_id',
                'linkedin_profile_url'
            ]);
        });
    }
};
