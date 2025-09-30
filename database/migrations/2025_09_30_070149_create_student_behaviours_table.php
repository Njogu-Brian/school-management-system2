<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_behaviours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('behaviour_id')->constrained('behaviours')->cascadeOnDelete();
            $table->foreignId('logged_by')->constrained('staff')->cascadeOnDelete();
            $table->date('date')->default(now());
            $table->enum('severity',['minor','moderate','major'])->default('minor');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('student_behaviours');
    }
};
