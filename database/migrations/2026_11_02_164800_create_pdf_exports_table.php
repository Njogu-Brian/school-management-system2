<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('title');
            $table->string('original_filename');
            $table->string('storage_disk', 64);
            $table->string('storage_path');
            $table->string('mime_type', 128)->default('application/pdf');
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->unsignedInteger('item_count')->nullable();
            $table->string('module', 64)->default('general');
            $table->string('status', 32)->default('ready');
            $table->json('meta')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_exports');
    }
};
