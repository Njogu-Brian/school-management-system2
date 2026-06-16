<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open'); // open, closed
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['start_date', 'end_date']);
        });

        Schema::create('accounting_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods')->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 20)->default('draft'); // draft, active, closed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('accounting_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('accounting_budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('budget_amount', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['budget_id', 'account_id']);
        });

        Schema::table('voteheads', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('category')->constrained('accounts')->nullOnDelete();
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('currency')->constrained('accounts')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('version')->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->foreignId('accrual_journal_entry_id')->nullable()->after('processed_by')->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('payment_journal_entry_id')->nullable()->after('accrual_journal_entry_id')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('payment_journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_journal_entry_id');
            $table->dropConstrainedForeignId('accrual_journal_entry_id');
            $table->dropColumn('paid_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('voteheads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::dropIfExists('accounting_budget_lines');
        Schema::dropIfExists('accounting_budgets');
        Schema::dropIfExists('fiscal_periods');
    }
};
