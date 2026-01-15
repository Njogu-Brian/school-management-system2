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
        Schema::create('swimming_transaction_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_transaction_id')->constrained('bank_statement_transactions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('amount', 10, 2)->comment('Amount allocated to this student from the transaction');
            $table->enum('status', ['pending', 'allocated', 'reversed'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('allocated_at')->nullable();
            $table->timestamps();
            
            $table->index(['bank_statement_transaction_id', 'student_id']);
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swimming_transaction_allocations');
    }
};
