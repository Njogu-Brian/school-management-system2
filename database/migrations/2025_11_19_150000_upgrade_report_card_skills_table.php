<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('report_card_skills')) {
            return;
        }

        Schema::table('report_card_skills', function (Blueprint $table) {
            if (!Schema::hasColumn('report_card_skills', 'name')) {
                $table->string('name')->nullable()->after('skill_name');
            }

            if (Schema::hasTable('classrooms') && !Schema::hasColumn('report_card_skills', 'classroom_id')) {
                $table->foreignId('classroom_id')
                    ->nullable()
                    ->after('report_card_id')
                    ->constrained('classrooms')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('report_card_skills', 'description')) {
                $table->text('description')->nullable()->after('rating');
            }

            if (!Schema::hasColumn('report_card_skills', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });

        if (Schema::hasColumn('report_card_skills', 'name') && Schema::hasColumn('report_card_skills', 'skill_name')) {
            DB::statement('UPDATE report_card_skills SET name = COALESCE(name, skill_name)');
            DB::statement('UPDATE report_card_skills SET skill_name = COALESCE(skill_name, name)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('report_card_skills')) {
            return;
        }

        Schema::table('report_card_skills', function (Blueprint $table) {
            if (Schema::hasColumn('report_card_skills', 'classroom_id')) {
                $table->dropConstrainedForeignId('classroom_id');
            }

            foreach (['name', 'description', 'is_active'] as $column) {
                if (Schema::hasColumn('report_card_skills', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

