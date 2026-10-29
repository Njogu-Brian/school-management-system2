<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('journal_bulk_imports', function (Blueprint $t) {
            $t->id();
            $t->string('batch_code')->unique();
            $t->string('original_filename')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 32)->default('preview'); // preview|partial|completed|abandoned
            $t->unsignedInteger('total_rows')->default(0);
            $t->unsignedInteger('pending_rows')->default(0);
            $t->unsignedInteger('approved_rows')->default(0);
            $t->unsignedInteger('failed_rows')->default(0);
            $t->unsignedInteger('invalid_rows')->default(0);
            $t->decimal('credit_total', 14, 2)->default(0);
            $t->decimal('debit_total', 14, 2)->default(0);
            $t->text('notes')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('journal_bulk_import_rows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_bulk_import_id')->constrained('journal_bulk_imports')->cascadeOnDelete();
            $t->string('uid', 64)->index();
            $t->unsignedInteger('line')->nullable();
            $t->string('admission_number')->nullable();
            $t->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $t->string('student_name')->nullable();
            $t->foreignId('votehead_id')->nullable()->constrained('voteheads')->nullOnDelete();
            $t->string('votehead_name')->nullable();
            $t->date('effective_date')->nullable();
            $t->string('type', 16)->nullable(); // credit|debit
            $t->string('type_label', 8)->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->unsignedTinyInteger('term')->nullable();
            $t->string('reason')->nullable();
            $t->decimal('amount', 14, 2)->default(0);
            $t->decimal('before_amount', 14, 2)->nullable();
            $t->decimal('new_amount', 14, 2)->nullable();
            $t->decimal('invoice_balance_before', 14, 2)->nullable();
            $t->decimal('invoice_balance_after', 14, 2)->nullable();
            $t->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->string('status', 32)->default('pending'); // pending|approved|failed|invalid
            $t->string('message')->nullable();
            $t->timestamps();

            $t->index(['journal_bulk_import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_bulk_import_rows');
        Schema::dropIfExists('journal_bulk_imports');
    }
};
