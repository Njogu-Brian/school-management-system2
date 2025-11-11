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
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->foreignId('salary_structure_id')->nullable()->constrained()->onDelete('set null');
            
            // Earnings
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('medical_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->text('allowances_breakdown')->nullable(); // JSON
            $table->decimal('gross_salary', 12, 2)->default(0);
            
            // Deductions
            $table->decimal('nssf_deduction', 12, 2)->default(0);
            $table->decimal('nhif_deduction', 12, 2)->default(0);
            $table->decimal('paye_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->text('deductions_breakdown')->nullable(); // JSON
            $table->decimal('total_deductions', 12, 2)->default(0);
            
            // Net pay
            $table->decimal('net_salary', 12, 2)->default(0);
            
            // Adjustments (bonuses, advances, etc.)
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('advance', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->text('adjustments_notes')->nullable();
            
            // Days worked (for prorating)
            $table->integer('days_worked')->nullable();
            $table->integer('days_in_period')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->timestamp('paid_at')->nullable();
            
            // Payslip
            $table->string('payslip_number')->unique()->nullable();
            $table->timestamp('payslip_generated_at')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('payroll_period_id');
            $table->index('staff_id');
            $table->index('status');
            $table->index('payslip_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
