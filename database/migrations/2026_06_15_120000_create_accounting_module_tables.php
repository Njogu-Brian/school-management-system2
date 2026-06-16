<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('account_type', 20); // asset, liability, equity, revenue, expense
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->enum('normal_balance', ['dr', 'cr']);
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['account_type', 'is_active']);
            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no', 40)->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('status', 20)->default('posted'); // draft, posted, reversed
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['entry_date', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->unsignedSmallInteger('line_order')->default(0);
            $table->timestamps();
            $table->index(['account_id', 'journal_entry_id']);
        });

        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('imprest_amount', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('petty_cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no', 40)->unique();
            $table->foreignId('petty_cash_fund_id')->constrained('petty_cash_funds')->restrictOnDelete();
            $table->string('voucher_type', 20); // disbursement, replenishment
            $table->date('voucher_date');
            $table->string('payee')->nullable();
            $table->text('description');
            $table->decimal('amount', 14, 2);
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft, approved, posted
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['petty_cash_fund_id', 'voucher_date']);
            $table->index(['status', 'voucher_type']);
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('parent_id')->constrained('accounts')->nullOnDelete();
            $table->boolean('is_header')->default(false)->after('account_id');
            $table->text('description')->nullable()->after('is_header');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('description');
        });

        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('approved_by')->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('expense_payments', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('account_source')->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->after('bank_account_id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expense_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropConstrainedForeignId('bank_account_id');
        });

        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropColumn(['is_header', 'description', 'sort_order']);
        });

        Schema::dropIfExists('petty_cash_vouchers');
        Schema::dropIfExists('petty_cash_funds');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
