<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_debit_note_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year');
            $table->unsignedTinyInteger('term');
            $table->foreignId('votehead_id')->nullable()->constrained('voteheads')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->unsignedInteger('notes_imported_count')->default(0);
            $table->decimal('total_credit_amount', 12, 2)->default(0);
            $table->decimal('total_debit_amount', 12, 2)->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['year', 'term', 'votehead_id', 'is_reversed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_debit_note_imports');
    }
};

