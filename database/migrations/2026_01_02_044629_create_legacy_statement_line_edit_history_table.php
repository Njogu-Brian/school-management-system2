<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legacy_statement_line_edit_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('legacy_statement_lines')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('legacy_finance_import_batches')->cascadeOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Before values (stored as JSON for flexibility)
            $table->json('before_values')->nullable();
            
            // After values (stored as JSON for flexibility)
            $table->json('after_values')->nullable();
            
            // Which fields were changed
            $table->json('changed_fields')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['line_id', 'created_at']);
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_statement_line_edit_history');
    }
};
