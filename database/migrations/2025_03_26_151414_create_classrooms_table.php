<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('classrooms')) {
            Schema::create('classrooms', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., Grade 1A, Form 3B
                $table->timestamps();
                
                // Additional columns will be added in later migrations
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};

