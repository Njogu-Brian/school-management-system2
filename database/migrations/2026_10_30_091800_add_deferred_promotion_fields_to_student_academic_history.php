<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_academic_history', function (Blueprint $table) {
            if (! Schema::hasColumn('student_academic_history', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('promotion_date');
            }
            if (! Schema::hasColumn('student_academic_history', 'applied_at')) {
                $table->timestamp('applied_at')->nullable()->after('effective_date');
            }
        });

        // Rows that already moved live membership are treated as already applied.
        if (Schema::hasColumn('student_academic_history', 'applied_at')) {
            DB::table('student_academic_history')
                ->whereNull('applied_at')
                ->whereIn('promotion_status', ['promoted', 'graduated', 'demoted'])
                ->update([
                    'applied_at' => DB::raw('COALESCE(promotion_date, created_at, NOW())'),
                    'effective_date' => DB::raw('COALESCE(effective_date, DATE(COALESCE(promotion_date, created_at, NOW())))'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('student_academic_history', function (Blueprint $table) {
            if (Schema::hasColumn('student_academic_history', 'applied_at')) {
                $table->dropColumn('applied_at');
            }
            if (Schema::hasColumn('student_academic_history', 'effective_date')) {
                $table->dropColumn('effective_date');
            }
        });
    }
};
