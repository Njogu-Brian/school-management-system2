<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('label'); // e.g., Kitchen, Janitor, Nurse
            $table->unsignedBigInteger('staff_id');
            $table->json('classroom_ids')->nullable(); // restrict to specific classes
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_recipients');
    }
};
