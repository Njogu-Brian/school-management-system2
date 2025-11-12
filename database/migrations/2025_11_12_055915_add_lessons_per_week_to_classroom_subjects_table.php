<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classroom_subjects', function (Blueprint $table) {
            $table->integer('lessons_per_week')->default(5)->after('is_compulsory');
        });
    }

    public function down(): void
    {
        Schema::table('classroom_subjects', function (Blueprint $table) {
            $table->dropColumn('lessons_per_week');
        });
    }
};
