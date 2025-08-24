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
        // database/migrations/xxxx_xx_xx_create_optional_fees_table.php
        Schema::create('optional_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('votehead_id')->constrained()->onDelete('cascade');
            $table->integer('term');
            $table->integer('year');
            $table->decimal('amount', 10, 2)->nullable();
            $table->enum('status', ['billed', 'exempt'])->default('billed');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('optional_fees');
    }
};
