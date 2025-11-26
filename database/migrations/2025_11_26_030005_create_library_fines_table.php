<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_id')->nullable()->constrained('book_borrowings')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('reason', ['overdue', 'lost', 'damaged'])->default('overdue');
            $table->enum('status', ['pending', 'paid', 'waived'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('borrowing_id');
            $table->index('student_id');
            $table->index('status');
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_fines');
    }
};

