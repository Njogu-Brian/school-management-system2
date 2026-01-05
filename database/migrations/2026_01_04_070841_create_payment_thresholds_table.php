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
        Schema::create('payment_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->nullable()->constrained('terms')->onDelete('cascade');
            $table->foreignId('student_category_id')->nullable()->constrained('student_categories')->onDelete('cascade');
            $table->decimal('minimum_percentage', 5, 2); // Percentage required by term opening day
            $table->integer('final_deadline_day')->default(5); // Day of month (e.g., 5th)
            $table->integer('final_deadline_month_offset')->default(2); // Month offset from term opening (e.g., 2 = second month)
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Unique constraint: one threshold per term + category combination
            $table->unique(['term_id', 'student_category_id'], 'unique_term_category_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_thresholds');
    }
};
