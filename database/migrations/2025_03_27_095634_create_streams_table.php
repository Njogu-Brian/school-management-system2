<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('classroom_id');
            $table->timestamps();

            // Foreign key reference to classrooms
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
