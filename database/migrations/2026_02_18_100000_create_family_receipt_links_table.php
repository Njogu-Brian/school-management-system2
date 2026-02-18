<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_receipt_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->string('token', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('family_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_receipt_links');
    }
};
