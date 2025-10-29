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
        Schema::table('cv_interviews', function (Blueprint $table) {
            $table->decimal('avg_score', 5, 2)->nullable()->after('evaluation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_interviews', function (Blueprint $table) {
            $table->dropColumn('avg_score');
        });
    }
};
