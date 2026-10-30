<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_concern_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_concern_id')->constrained('student_concerns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_staff')->default(false);
            $table->timestamps();
            $table->index(['student_concern_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_concern_messages');
    }
};
