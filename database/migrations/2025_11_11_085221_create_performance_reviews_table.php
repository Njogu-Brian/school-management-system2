<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('staff')->onDelete('cascade');
            $table->string('review_type')->default('annual'); // annual, mid_year, probation, etc.
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->date('review_date');
            
            // Ratings (1-5 scale)
            $table->decimal('overall_rating', 3, 2)->nullable(); // 1.00 to 5.00
            $table->json('category_ratings')->nullable(); // {category: rating}
            
            // Review content
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('achievements')->nullable();
            $table->text('goals_met')->nullable();
            $table->text('comments')->nullable();
            $table->text('reviewer_comments')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'submitted', 'acknowledged', 'completed'])->default('draft');
            $table->timestamp('acknowledged_at')->nullable();
            
            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('reviewer_id');
            $table->index('review_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
