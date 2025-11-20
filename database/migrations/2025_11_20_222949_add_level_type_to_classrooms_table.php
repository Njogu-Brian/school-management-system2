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
        Schema::table('classrooms', function (Blueprint $table) {
            $table->enum('level_type', ['preschool', 'lower_primary', 'upper_primary', 'junior_high'])
                ->nullable()
                ->after('name')
                ->comment('Level category: preschool, lower_primary, upper_primary, junior_high');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('level_type');
        });
    }
};
