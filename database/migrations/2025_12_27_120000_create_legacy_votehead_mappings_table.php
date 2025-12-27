<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_votehead_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_label')->index();
            $table->foreignId('votehead_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'resolved'])->default('pending')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['legacy_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_votehead_mappings');
    }
};

