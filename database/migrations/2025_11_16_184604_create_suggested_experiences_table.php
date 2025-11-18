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
        Schema::create('suggested_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substrand_id')->constrained('cbc_substrands')->cascadeOnDelete();
            $table->text('content'); // Suggested learning experience description
            $table->text('examples')->nullable(); // Example activities or resources
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable(); // Additional structured data
            $table->timestamps();

            $table->index('substrand_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggested_experiences');
    }
};
