<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // CAT tracking
            if (!Schema::hasColumn('exams', 'is_cat')) {
                $table->boolean('is_cat')->default(false)->after('type');
            }

            if (!Schema::hasColumn('exams', 'cat_number')) {
                $table->integer('cat_number')->nullable()->after('is_cat'); // 1, 2, or 3
            }

            // Assessment method
            if (!Schema::hasColumn('exams', 'assessment_method')) {
                $table->enum('assessment_method', ['written', 'oral', 'practical', 'portfolio', 'mixed'])->default('written')->after('cat_number');
            }

            // Competency focus (JSON array of competency IDs)
            if (!Schema::hasColumn('exams', 'competency_focus')) {
                $table->json('competency_focus')->nullable()->after('assessment_method');
            }

            // Portfolio requirement
            if (!Schema::hasColumn('exams', 'portfolio_required')) {
                $table->boolean('portfolio_required')->default(false)->after('competency_focus');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'is_cat',
                'cat_number',
                'assessment_method',
                'competency_focus',
                'portfolio_required'
            ]);
        });
    }
};
