<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('source')->default('mpesa'); // mpesa, bank (future)
            $table->string('original_filename');
            $table->string('file_path');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('status')->default('parsed'); // uploaded, parsed, failed
            $table->unsignedInteger('line_count')->default(0);
            $table->unsignedInteger('outgoing_count')->default(0);
            $table->decimal('outgoing_total', 14, 2)->default(0);
            $table->decimal('confirmed_expense_total', 14, 2)->default(0);
            $table->text('parse_error')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->index(['source', 'status']);
            $table->index(['uploaded_by', 'created_at']);
        });

        Schema::create('expense_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('expense_statement_imports')->cascadeOnDelete();
            $table->string('receipt_no', 32)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('narration');
            $table->string('line_fingerprint', 64);
            $table->decimal('withdrawn_amount', 14, 2)->default(0);
            $table->decimal('paid_in_amount', 14, 2)->default(0);
            $table->string('direction', 8)->default('out'); // in, out
            $table->string('transaction_type', 32)->default('other');
            $table->boolean('is_transaction_fee')->default(false);
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 32)->nullable();
            $table->string('paybill_number', 32)->nullable();
            $table->string('account_reference', 255)->nullable();
            $table->string('merchant_reference', 255)->nullable();
            $table->string('group_key', 64)->index();
            $table->string('review_status', 24)->default('pending'); // pending, confirmed_expense, personal, ignored
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->text('expense_description')->nullable();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            $table->unique(['import_id', 'line_fingerprint'], 'exp_stmt_line_unique');
            $table->index(['import_id', 'group_key']);
            $table->index(['import_id', 'review_status']);
        });

        Schema::create('expense_statement_recipient_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 64)->unique();
            $table->string('display_name');
            $table->string('transaction_type', 32);
            $table->boolean('is_business_expense')->default(false);
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->text('default_description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_statement_recipient_profiles');
        Schema::dropIfExists('expense_statement_lines');
        Schema::dropIfExists('expense_statement_imports');
    }
};
