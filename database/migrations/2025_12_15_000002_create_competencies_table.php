<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only create if cbc_substrands table exists
        if (Schema::hasTable('cbc_substrands') && !Schema::hasTable('competencies')) {
            Schema::create('competencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('substrand_id')->constrained('cbc_substrands')->cascadeOnDelete();
                $table->string('code', 50)->unique(); // e.g., ENG.R.IR.1
                $table->string('name');
                $table->text('description')->nullable();
                $table->text('indicators')->nullable(); // JSON array of competency indicators
                $table->text('assessment_criteria')->nullable(); // How to assess this competency
                $table->string('competency_level')->nullable(); // e.g., Basic, Intermediate, Advanced
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('substrand_id');
                $table->index('code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('competencies');
    }
};

