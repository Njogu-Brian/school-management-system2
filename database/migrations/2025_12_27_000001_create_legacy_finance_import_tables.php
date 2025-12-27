<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_finance_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('class_label')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_students')->default(0);
            $table->unsignedInteger('imported_students')->default(0);
            $table->unsignedInteger('draft_students')->default(0);
            $table->timestamps();
        });

        Schema::create('legacy_statement_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_finance_import_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('admission_number')->index();
            $table->string('student_name')->nullable();
            $table->unsignedSmallInteger('academic_year')->nullable();
            $table->string('term_name', 20)->nullable(); // JAN-APR, MAY-AUG, SEP-DEC
            $table->unsignedTinyInteger('term_number')->nullable(); // 1, 2, 3
            $table->string('class_label', 50)->nullable(); // e.g. GRADE 1
            $table->string('source_label')->nullable(); // full header text as printed
            $table->decimal('starting_balance', 12, 2)->nullable();
            $table->decimal('ending_balance', 12, 2)->nullable();
            $table->string('status')->default('imported'); // imported | draft
            $table->string('confidence')->default('high'); // high | draft
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_finance_import_batches')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('legacy_statement_terms')->cascadeOnDelete();
            $table->date('txn_date')->nullable();
            $table->text('narration_raw');
            $table->string('txn_type', 30)->nullable(); // invoice | receipt | credit_note | debit_note | balance_bf | other
            $table->string('votehead')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('linked_invoice_ref')->nullable();
            $table->string('channel', 50)->nullable(); // MPESA, CASH, etc
            $table->string('txn_code', 100)->nullable();
            $table->decimal('amount_dr', 12, 2)->nullable();
            $table->decimal('amount_cr', 12, 2)->nullable();
            $table->decimal('running_balance', 12, 2)->nullable();
            $table->string('confidence')->default('high'); // high | draft
            $table->unsignedInteger('sequence_no')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['term_id', 'sequence_no']);
            $table->index(['reference_number', 'txn_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_statement_lines');
        Schema::dropIfExists('legacy_statement_terms');
        Schema::dropIfExists('legacy_finance_import_batches');
    }
};

