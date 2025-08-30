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
        Schema::create('kitchen_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('label'); // e.g. Chef, Upper Janitor, Lower Janitor
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->json('classroom_ids')->nullable(); // JSON array of assigned classes
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_recipients');
    }
};
