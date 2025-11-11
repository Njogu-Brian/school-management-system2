<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_name');
            $table->string('course_code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->string('provider')->nullable();
            $table->integer('duration_hours')->nullable();
            $table->enum('delivery_method', ['classroom', 'online', 'blended', 'workshop'])->default('classroom');
            $table->decimal('cost_per_participant', 10, 2)->nullable();
            $table->integer('max_participants')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('prerequisites')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_courses');
    }
};
