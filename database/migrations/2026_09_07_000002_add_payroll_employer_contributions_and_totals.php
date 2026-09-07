<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->decimal('employer_nssf_contribution', 12, 2)->default(0)->after('nssf_deduction');
            $table->decimal('employer_housing_levy_contribution', 12, 2)->default(0)->after('housing_levy_deduction');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->decimal('total_employee_statutory', 14, 2)->default(0)->after('total_net');
            $table->decimal('total_employer_statutory', 14, 2)->default(0)->after('total_employee_statutory');
            $table->decimal('total_loan_repayments', 14, 2)->default(0)->after('total_employer_statutory');
            $table->decimal('total_amount_required', 14, 2)->default(0)->after('total_loan_repayments');
        });

        DB::table('payroll_records')->update([
            'employer_nssf_contribution' => DB::raw('COALESCE(nssf_deduction, 0)'),
            'employer_housing_levy_contribution' => DB::raw('COALESCE(housing_levy_deduction, 0)'),
        ]);

        DB::statement("UPDATE payroll_periods p LEFT JOIN (SELECT payroll_period_id, SUM(CASE WHEN status <> 'cancelled' THEN COALESCE(nssf_deduction, 0) + COALESCE(nhif_deduction, 0) + COALESCE(shif_deduction, 0) + COALESCE(paye_deduction, 0) + COALESCE(housing_levy_deduction, 0) ELSE 0 END) AS employee_statutory, SUM(CASE WHEN status <> 'cancelled' THEN COALESCE(employer_nssf_contribution, 0) + COALESCE(employer_housing_levy_contribution, 0) ELSE 0 END) AS employer_statutory, SUM(CASE WHEN status <> 'cancelled' THEN COALESCE(advance_deduction, 0) ELSE 0 END) AS loan_repayments, SUM(CASE WHEN status <> 'cancelled' THEN COALESCE(net_salary, 0) ELSE 0 END) AS net_salary FROM payroll_records GROUP BY payroll_period_id) totals ON totals.payroll_period_id = p.id SET p.total_employee_statutory = COALESCE(totals.employee_statutory, 0), p.total_employer_statutory = COALESCE(totals.employer_statutory, 0), p.total_loan_repayments = COALESCE(totals.loan_repayments, 0), p.total_amount_required = COALESCE(totals.net_salary, 0) + COALESCE(totals.employee_statutory, 0) + COALESCE(totals.employer_statutory, 0) + COALESCE(totals.loan_repayments, 0)");
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn(['total_employee_statutory', 'total_employer_statutory', 'total_loan_repayments', 'total_amount_required']);
        });

        Schema::table('payroll_records', function (Blueprint $table) {
            $table->dropColumn(['employer_nssf_contribution', 'employer_housing_levy_contribution']);
        });
    }
};