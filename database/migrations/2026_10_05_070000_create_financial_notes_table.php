<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->text('body');
            $table->date('promise_date')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'created_at']);
            $table->index(['family_id', 'created_at']);
            $table->index('promise_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_notes');
    }
};
