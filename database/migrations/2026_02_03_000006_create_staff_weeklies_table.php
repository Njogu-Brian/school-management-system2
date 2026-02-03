<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_weeklies', function (Blueprint $table) {
            $table->id();
            $table->date('week_ending');
            $table->enum('campus', ['lower', 'upper'])->nullable();
            $table->unsignedBigInteger('staff_id');
            $table->boolean('on_time_all_week')->nullable();
            $table->unsignedInteger('lessons_missed')->nullable();
            $table->boolean('books_marked')->nullable();
            $table->boolean('schemes_updated')->nullable();
            $table->enum('class_control', ['Good', 'Fair', 'Poor'])->nullable();
            $table->enum('general_performance', ['Excellent', 'Good', 'Fair', 'Poor'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->index(['week_ending', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_weeklies');
    }
};
