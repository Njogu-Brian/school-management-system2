<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->foreignId('performance_review_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable(); // professional_development, performance, etc.
            $table->date('start_date');
            $table->date('target_date');
            $table->date('completion_date')->nullable();
            
            // Progress tracking
            $table->integer('progress_percentage')->default(0); // 0-100
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'cancelled'])->default('not_started');
            
            // Measurement
            $table->text('success_criteria')->nullable();
            $table->text('key_results')->nullable(); // JSON array
            
            // Review
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('performance_review_id');
            $table->index('status');
            $table->index('target_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_goals');
    }
};
