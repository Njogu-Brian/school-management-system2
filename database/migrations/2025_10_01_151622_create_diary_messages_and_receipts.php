<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('diary_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diary_id')->constrained('diaries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('message_type',['text','image','file','system'])->default('text');
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        Schema::create('diary_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('diary_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['message_id','user_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('diary_read_receipts');
        Schema::dropIfExists('diary_messages');
    }
};
