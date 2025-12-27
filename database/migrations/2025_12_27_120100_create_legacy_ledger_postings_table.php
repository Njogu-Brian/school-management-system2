<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_ledger_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('legacy_finance_import_batches')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('legacy_statement_terms')->cascadeOnDelete();
            $table->foreignId('line_id')->nullable()->constrained('legacy_statement_lines')->nullOnDelete();
            $table->string('target_type'); // invoice|payment|credit|debit|discount|opening
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('hash')->index();
            $table->enum('status', ['pending', 'posted', 'skipped', 'error'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['line_id', 'target_type', 'hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_ledger_postings');
    }
};

