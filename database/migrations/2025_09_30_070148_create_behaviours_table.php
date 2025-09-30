<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('behaviours', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Respect, Bullying, Punctuality
            $table->enum('type',['positive','negative'])->default('positive');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('behaviours');
    }
};
