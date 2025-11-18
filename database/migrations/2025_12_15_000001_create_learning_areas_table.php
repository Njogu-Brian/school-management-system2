<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_areas')) {
            Schema::create('learning_areas', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique(); // e.g., ENG, MATH, SCI, KIS
                $table->string('name'); // English, Mathematics, Science, Kiswahili
                $table->text('description')->nullable();
                $table->string('level_category')->nullable(); // Pre-Primary, Lower Primary, Upper Primary, Junior Secondary, Senior Secondary
                $table->json('levels')->nullable(); // Array of levels this learning area applies to
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_core')->default(true); // Core or optional learning area
                $table->timestamps();

                $table->index('code');
                $table->index('level_category');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_areas');
    }
};

