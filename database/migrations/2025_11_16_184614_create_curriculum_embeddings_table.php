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
        Schema::create('curriculum_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_design_id')->constrained('curriculum_designs')->cascadeOnDelete();
            $table->enum('source_type', ['page', 'section', 'competency', 'strand', 'substrand', 'rubric', 'experience'])->default('section');
            $table->unsignedBigInteger('source_id')->nullable(); // ID of the source entity (page_id, competency_id, etc.)
            $table->text('text_snippet'); // The text that was embedded
            $table->string('vector_store_id')->nullable(); // External vector DB ID (for Weaviate/Milvus/Qdrant)
            // For pgvector, we'll add the vector column separately if pgvector extension is available
            $table->json('metadata')->nullable(); // Additional metadata (page numbers, confidence, etc.)
            $table->timestamps();

            $table->index('curriculum_design_id');
            $table->index(['source_type', 'source_id']);
            $table->index('vector_store_id');
        });

        // Add vector column if pgvector extension is available
        // This will be handled conditionally in the migration
        // For now, we'll use vector_store_id for external stores
        // To enable pgvector, run: CREATE EXTENSION IF NOT EXISTS vector;
        // Then add: $table->vector('embedding', 384)->nullable(); // 384 is default for sentence-transformers
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_embeddings');
    }
};
