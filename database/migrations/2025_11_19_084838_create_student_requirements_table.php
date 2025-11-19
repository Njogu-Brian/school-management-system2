<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('requirement_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('quantity_required', 10, 2);
            $table->decimal('quantity_collected', 10, 2)->default(0);
            $table->decimal('quantity_missing', 10, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'complete', 'missing'])->default('pending');
            $table->timestamp('collected_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('notified_parent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requirements');
    }
};
