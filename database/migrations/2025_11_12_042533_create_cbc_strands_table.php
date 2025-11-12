<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbc_strands', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // e.g., LA1, MA1, SCI1
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('learning_area'); // Language, Mathematics, Science, etc.
            $table->string('level'); // PP1, PP2, Grade 1-9
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['learning_area', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbc_strands');
    }
};
