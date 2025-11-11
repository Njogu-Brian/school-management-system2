<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->string('skill_name');
            $table->string('category')->nullable(); // technical, soft, language, etc.
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->integer('years_of_experience')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_certified')->default(false);
            $table->string('certification_body')->nullable();
            $table->date('certification_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('category');
            $table->index('proficiency_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_skills');
    }
};
