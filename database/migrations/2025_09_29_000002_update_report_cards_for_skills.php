<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('report_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('report_cards', 'career_interest')) {
                $table->string('career_interest')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('report_cards', 'talent_noticed')) {
                $table->string('talent_noticed')->nullable()->after('career_interest');
            }
            if (!Schema::hasColumn('report_cards', 'teacher_remark')) {
                $table->text('teacher_remark')->nullable()->after('talent_noticed');
            }
            if (!Schema::hasColumn('report_cards', 'headteacher_remark')) {
                $table->text('headteacher_remark')->nullable()->after('teacher_remark');
            }
            if (!Schema::hasColumn('report_cards', 'public_token')) {
                $table->string('public_token', 64)->nullable()->unique()->after('locked_at');
            }
        });
    }
    public function down(): void {
        Schema::table('report_cards', function (Blueprint $table) {
            foreach (['career_interest','talent_noticed','teacher_remark','headteacher_remark','public_token'] as $c) {
                if (Schema::hasColumn('report_cards', $c)) $table->dropColumn($c);
            }
        });
    }
};
