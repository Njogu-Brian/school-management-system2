<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbc_substrands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strand_id')->constrained('cbc_strands')->cascadeOnDelete();
            $table->string('code', 20); // e.g., LA1.1, MA1.2
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('learning_outcomes')->nullable(); // JSON or text
            $table->text('key_inquiry_questions')->nullable(); // JSON array
            $table->text('core_competencies')->nullable(); // JSON array of competency codes
            $table->text('values')->nullable(); // JSON array
            $table->text('pclc')->nullable(); // Pertinent and Contemporary Issues
            $table->integer('suggested_lessons')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['strand_id', 'code']);
            $table->index('strand_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbc_substrands');
    }
};
