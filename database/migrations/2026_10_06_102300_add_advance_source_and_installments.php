<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_advances', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_advances', 'source_type')) {
                $table->string('source_type', 20)->default('company')->after('created_by');
            }
            if (! Schema::hasColumn('staff_advances', 'source_staff_id')) {
                $table->foreignId('source_staff_id')->nullable()->after('source_type')
                    ->constrained('staff')->nullOnDelete();
            }
            if (! Schema::hasColumn('staff_advances', 'repayment_start_year')) {
                $table->unsignedSmallInteger('repayment_start_year')->nullable()->after('source_staff_id');
            }
            if (! Schema::hasColumn('staff_advances', 'repayment_start_month')) {
                $table->unsignedTinyInteger('repayment_start_month')->nullable()->after('repayment_start_year');
            }
        });

        if (! Schema::hasTable('staff_advance_installments')) {
            Schema::create('staff_advance_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_advance_id')->constrained('staff_advances')->cascadeOnDelete();
                $table->unsignedSmallInteger('sequence');
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->decimal('amount', 12, 2);
                $table->string('status', 20)->default('pending'); // pending|collected|skipped
                $table->foreignId('borrower_payroll_record_id')->nullable()
                    ->constrained('payroll_records')->nullOnDelete();
                $table->foreignId('funder_payroll_record_id')->nullable()
                    ->constrained('payroll_records')->nullOnDelete();
                $table->timestamps();

                $table->unique(['staff_advance_id', 'sequence']);
                $table->index(['year', 'month', 'status']);
            });
        }

        Schema::table('payroll_records', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_records', 'advance_reimbursement')) {
                $table->decimal('advance_reimbursement', 12, 2)->default(0)->after('advance_deduction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_records', 'advance_reimbursement')) {
                $table->dropColumn('advance_reimbursement');
            }
        });

        Schema::dropIfExists('staff_advance_installments');

        Schema::table('staff_advances', function (Blueprint $table) {
            if (Schema::hasColumn('staff_advances', 'source_staff_id')) {
                $table->dropConstrainedForeignId('source_staff_id');
            }
            foreach (['source_type', 'repayment_start_year', 'repayment_start_month'] as $col) {
                if (Schema::hasColumn('staff_advances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
