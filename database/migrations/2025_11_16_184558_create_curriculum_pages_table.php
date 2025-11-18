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
        Schema::create('curriculum_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_design_id')->constrained('curriculum_designs')->cascadeOnDelete();
            $table->integer('page_number');
            $table->text('text')->nullable(); // Extracted text content
            $table->decimal('ocr_confidence', 5, 2)->nullable(); // OCR confidence score (0-100)
            $table->text('raw_html')->nullable(); // Raw HTML if extracted from PDF
            $table->timestamps();

            $table->unique(['curriculum_design_id', 'page_number']);
            $table->index('curriculum_design_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_pages');
    }
};
