<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_records', 'shif_deduction')) {
                $table->decimal('shif_deduction', 12, 2)->default(0)->after('nhif_deduction');
            }
            if (! Schema::hasColumn('payroll_records', 'housing_levy_deduction')) {
                $table->decimal('housing_levy_deduction', 12, 2)->default(0)->after('paye_deduction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_records', 'housing_levy_deduction')) {
                $table->dropColumn('housing_levy_deduction');
            }
            if (Schema::hasColumn('payroll_records', 'shif_deduction')) {
                $table->dropColumn('shif_deduction');
            }
        });
    }
};

