<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_pin')->nullable();
            $table->integer('payable_terms')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['name', 'is_active']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no')->unique();
            $table->string('source_type')->default('vendor_bill');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->date('expense_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'expense_date']);
            $table->index(['vendor_id', 'due_date']);
        });

        Schema::create('expense_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('department')->nullable();
            $table->string('cost_center')->nullable();
            $table->text('description');
            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->timestamps();
            $table->index(['category_id', 'department']);
        });

        Schema::create('expense_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->foreignId('approved_by')->constrained('users');
            $table->string('decision');
            $table->timestamp('decided_at');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['expense_id', 'decision']);
        });

        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->string('payee');
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'payment_date']);
        });

        Schema::create('expense_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('payment_vouchers')->cascadeOnDelete();
            $table->string('reference_no')->nullable();
            $table->string('account_source')->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->index(['paid_at', 'account_source']);
        });

        Schema::create('expense_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('ledger_postings', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('account_code');
            $table->enum('dr_cr', ['dr', 'cr']);
            $table->decimal('amount', 14, 2);
            $table->date('posting_date');
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
            $table->index(['account_code', 'posting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_postings');
        Schema::dropIfExists('expense_attachments');
        Schema::dropIfExists('expense_payments');
        Schema::dropIfExists('payment_vouchers');
        Schema::dropIfExists('expense_approvals');
        Schema::dropIfExists('expense_lines');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('expense_categories');
    }
};
