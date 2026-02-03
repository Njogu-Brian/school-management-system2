<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_senior_teachers', function (Blueprint $table) {
            $table->id();
            $table->enum('campus', ['lower', 'upper'])->unique();
            $table->unsignedBigInteger('senior_teacher_id');
            $table->timestamps();

            $table->foreign('senior_teacher_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_senior_teachers');
    }
};
