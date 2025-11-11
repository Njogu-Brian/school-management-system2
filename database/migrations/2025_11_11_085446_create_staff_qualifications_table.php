<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->string('qualification_type'); // degree, diploma, certificate, etc.
            $table->string('qualification_name');
            $table->string('institution');
            $table->string('field_of_study')->nullable();
            $table->year('year_obtained')->nullable();
            $table->string('grade_classification')->nullable(); // First Class, Second Class Upper, etc.
            $table->string('certificate_number')->nullable();
            $table->string('certificate_file')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->date('verification_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('qualification_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_qualifications');
    }
};
