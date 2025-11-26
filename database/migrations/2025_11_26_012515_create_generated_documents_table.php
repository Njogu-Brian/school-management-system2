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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('document_type', [
                'certificate',
                'transcript',
                'id_card',
                'transfer_certificate',
                'character_certificate',
                'diploma',
                'merit_certificate',
                'participation_certificate',
                'custom'
            ]);
            $table->string('pdf_path')->nullable();
            $table->json('data')->nullable(); // Data used for generation (for regeneration)
            $table->string('filename')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            
            $table->index('template_id');
            $table->index('student_id');
            $table->index('staff_id');
            $table->index('document_type');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};

