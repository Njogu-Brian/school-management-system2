<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'campus')) {
                $table->enum('campus', ['lower', 'upper'])->nullable()->after('name');
            }
            if (!Schema::hasColumn('classrooms', 'academic_group')) {
                $table->string('academic_group')->nullable()->after('campus');
            }
            if (!Schema::hasColumn('classrooms', 'level')) {
                $table->string('level')->nullable()->after('academic_group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            if (Schema::hasColumn('classrooms', 'campus')) {
                $table->dropColumn('campus');
            }
            if (Schema::hasColumn('classrooms', 'academic_group')) {
                $table->dropColumn('academic_group');
            }
            if (Schema::hasColumn('classrooms', 'level')) {
                $table->dropColumn('level');
            }
        });
    }
};
