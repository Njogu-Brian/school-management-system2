<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('payroll_records', 'advance_deduction')) {
                // If 'advance' column exists, we'll keep both for now (data migration can be done separately)
                if (Schema::hasColumn('payroll_records', 'advance')) {
                    $table->decimal('advance_deduction', 12, 2)->default(0)->after('bonus');
                } else {
                    $table->decimal('advance_deduction', 12, 2)->default(0)->after('bonus');
                }
            }
            
            // Remove loan_deduction (will be handled by custom deductions)
            if (Schema::hasColumn('payroll_records', 'loan_deduction')) {
                $table->dropColumn('loan_deduction');
            }
            
            // Add custom deductions columns
            if (!Schema::hasColumn('payroll_records', 'custom_deductions_total')) {
                $table->decimal('custom_deductions_total', 12, 2)->default(0)->after('advance_deduction');
            }
            
            if (!Schema::hasColumn('payroll_records', 'custom_deductions_breakdown')) {
                $table->json('custom_deductions_breakdown')->nullable()->after('custom_deductions_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_records', 'custom_deductions_total')) {
                $table->dropColumn('custom_deductions_total');
            }
            
            if (Schema::hasColumn('payroll_records', 'custom_deductions_breakdown')) {
                $table->dropColumn('custom_deductions_breakdown');
            }
            
            if (Schema::hasColumn('payroll_records', 'advance_deduction')) {
                $table->dropColumn('advance_deduction');
            }
            
            if (!Schema::hasColumn('payroll_records', 'loan_deduction')) {
                $table->decimal('loan_deduction', 12, 2)->default(0)->after('bonus');
            }
        });
    }
};
