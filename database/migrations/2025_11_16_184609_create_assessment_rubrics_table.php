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
        Schema::create('assessment_rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substrand_id')->constrained('cbc_substrands')->cascadeOnDelete();
            $table->json('rubric_json'); // Structured rubric data
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('substrand_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_rubrics');
    }
};
