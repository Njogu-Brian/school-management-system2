<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_posting_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->enum('run_type', ['dry_run', 'commit', 'reversal'])->default('dry_run');
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reversed_at')->nullable();
            $table->json('filters_applied')->nullable(); // Store filters used for this run
            $table->integer('items_posted_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'term_id']);
            $table->index('status');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_posting_runs');
    }
};

