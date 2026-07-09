<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'payment_method')) {
                // bank | mpesa
                $table->string('payment_method', 16)->default('bank')->after('bank_account');
            }
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_periods', 'expense_id')) {
                $table->foreignId('expense_id')->nullable()->after('payment_journal_entry_id')
                    ->constrained('expenses')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'expense_id')) {
                $table->dropConstrainedForeignId('expense_id');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
