<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->foreignId('feedback_provider_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('performance_review_id')->nullable()->constrained()->onDelete('set null');
            $table->string('feedback_type')->default('peer'); // peer, supervisor, subordinate, self, 360
            $table->text('feedback');
            $table->json('ratings')->nullable(); // {category: rating}
            $table->boolean('is_anonymous')->default(false);
            $table->date('feedback_date');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('feedback_provider_id');
            $table->index('performance_review_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_feedback');
    }
};
