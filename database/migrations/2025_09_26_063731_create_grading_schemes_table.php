<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('grading_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['numeric_letter','cbc_pl']);
            $table->json('meta')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('grading_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scheme_id')->constrained('grading_schemes')->cascadeOnDelete();
            $table->decimal('min',5,2)->unsigned();
            $table->decimal('max',5,2)->unsigned();
            $table->string('label');       // e.g., A, B or PL1
            $table->string('descriptor')->nullable();
            $table->unsignedInteger('rank')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('grading_bands');
        Schema::dropIfExists('grading_schemes');
    }
};
