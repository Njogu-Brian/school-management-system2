<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('requirement_templates')) {
            return;
        }

        if (Schema::hasTable('requirement_template_assignments')) {
            return;
        }

        Schema::create('requirement_template_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requirement_template_id')
                ->constrained('requirement_templates')
                ->onDelete('cascade');

            $table->foreignId('academic_year_id')
                ->nullable()
                ->constrained('academic_years')
                ->nullOnDelete();

            $table->foreignId('term_id')
                ->nullable()
                ->constrained('terms')
                ->nullOnDelete();

            $table->foreignId('classroom_id')
                ->nullable()
                ->constrained('classrooms')
                ->nullOnDelete();

            $table->enum('student_type', ['new', 'existing', 'both'])->default('both');

            $table->string('brand')->nullable();
            $table->decimal('quantity_per_student', 10, 2)->default(1);
            $table->string('unit')->default('piece');
            $table->text('notes')->nullable();

            $table->boolean('leave_with_teacher')->default(false);
            $table->boolean('is_verification_only')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['requirement_template_id', 'academic_year_id', 'term_id', 'classroom_id', 'student_type'],
                'rta_template_scope_unique'
            );

            $table->index(['academic_year_id', 'term_id', 'classroom_id', 'student_type'], 'rta_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_template_assignments');
    }
};

