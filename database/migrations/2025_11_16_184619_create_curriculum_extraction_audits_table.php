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
        Schema::create('curriculum_extraction_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_design_id')->constrained('curriculum_designs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // e.g., 'upload', 'extract', 'review', 'accept', 'reject', 'edit'
            $table->text('notes')->nullable();
            $table->json('changes')->nullable(); // JSON diff of what changed
            $table->timestamps();

            $table->index('curriculum_design_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_extraction_audits');
    }
};
