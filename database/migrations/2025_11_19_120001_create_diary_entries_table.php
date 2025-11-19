<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_diary_id')->constrained('student_diaries')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete(); // Who created the entry
            $table->string('author_type')->default('user'); // user, parent, teacher, admin
            $table->foreignId('parent_entry_id')->nullable()->constrained('diary_entries')->nullOnDelete(); // For replies/threading
            $table->text('content'); // Entry content
            $table->json('attachments')->nullable(); // Array of file paths
            $table->boolean('is_read')->default(false); // Read status
            $table->timestamps();

            $table->index(['student_diary_id', 'created_at']);
            $table->index('parent_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_entries');
    }
};

