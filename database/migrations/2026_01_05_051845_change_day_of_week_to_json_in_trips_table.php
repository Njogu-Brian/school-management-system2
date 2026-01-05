<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes day_of_week from single integer to JSON array to support multiple days
     */
    public function up(): void
    {
        // First, convert existing integer values to JSON arrays
        DB::statement("UPDATE trips SET day_of_week = JSON_ARRAY(day_of_week) WHERE day_of_week IS NOT NULL");
        
        // Then change the column type to JSON
        Schema::table('trips', function (Blueprint $table) {
            DB::statement('ALTER TABLE trips MODIFY day_of_week JSON NULL COMMENT \'Array of days (1=Monday, 7=Sunday), NULL=all days\'');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, convert JSON arrays back to single integers (take first value)
        DB::statement("UPDATE trips SET day_of_week = CAST(JSON_UNQUOTE(JSON_EXTRACT(day_of_week, '$[0]')) AS UNSIGNED) WHERE day_of_week IS NOT NULL AND JSON_LENGTH(day_of_week) > 0");
        
        // Then change the column type back to tinyInteger
        Schema::table('trips', function (Blueprint $table) {
            DB::statement('ALTER TABLE trips MODIFY day_of_week TINYINT NULL COMMENT \'1=Monday, 7=Sunday, NULL=all days\'');
        });
    }
};
