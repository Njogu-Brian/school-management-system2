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
        Schema::create('exam_grades', function (Blueprint $table) {
        $table->id();
        $table->string('exam_type'); // CAT, MIDTERM, ENDTERM etc.
        $table->string('grade_name'); // A, A+, B
        $table->decimal('percent_from', 5,2);
        $table->decimal('percent_upto', 5,2);
        $table->decimal('grade_point', 3,2)->nullable();
        $table->string('description')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_grades');
    }
};
