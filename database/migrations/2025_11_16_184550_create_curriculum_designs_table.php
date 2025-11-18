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
        Schema::create('curriculum_designs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('class_level', 50)->nullable(); // e.g., "Grade 4", "PP1", "Form 1"
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('file_path'); // Path to stored PDF
            $table->integer('pages')->default(0);
            $table->enum('status', ['processing', 'processed', 'failed'])->default('processing');
            $table->string('checksum', 64)->nullable(); // SHA-256 hash for duplicate detection
            $table->json('metadata')->nullable(); // Additional metadata (extraction stats, etc.)
            $table->text('error_notes')->nullable(); // Error details if processing failed
            $table->timestamps();

            $table->index('subject_id');
            $table->index('status');
            $table->index('class_level');
            $table->index('uploaded_by');
            $table->index('checksum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_designs');
    }
};
