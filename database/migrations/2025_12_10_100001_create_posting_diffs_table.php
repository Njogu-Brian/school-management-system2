<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_diffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posting_run_id')->constrained('fee_posting_runs')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('votehead_id')->constrained('voteheads')->onDelete('cascade');
            $table->enum('action', ['added', 'increased', 'decreased', 'unchanged', 'removed'])->default('added');
            $table->decimal('old_amount', 10, 2)->nullable();
            $table->decimal('new_amount', 10, 2)->nullable();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->onDelete('set null');
            $table->string('source')->nullable(); // structure, optional, transport, manual
            $table->timestamps();

            $table->index(['posting_run_id', 'student_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_diffs');
    }
};

