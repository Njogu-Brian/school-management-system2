<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirement_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->constrained('terms')->onDelete('cascade');
            $table->string('brand')->nullable();
            $table->decimal('quantity_per_student', 10, 2)->default(1);
            $table->string('unit')->default('piece');
            $table->enum('student_type', ['new', 'existing', 'both'])->default('both');
            $table->boolean('leave_with_teacher')->default(false); // true = stored in inventory, false = left with student
            $table->boolean('is_verification_only')->default(false); // for verification purposes
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_templates');
    }
};
