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
            $table->unsignedBigInteger('bank_statement_transaction_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('amount', 10, 2)->comment('Amount allocated to this student from the transaction');
            $table->enum('status', ['pending', 'allocated', 'reversed'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamps();
            
            // Add foreign keys with shorter constraint names (MySQL limit is 64 chars)
            $table->foreign('bank_statement_transaction_id', 'swim_txn_alloc_bank_txn_fk')
                  ->references('id')
                  ->on('bank_statement_transactions')
                  ->onDelete('cascade');
            
            $table->foreign('student_id', 'swim_txn_alloc_student_fk')
                  ->references('id')
                  ->on('students')
                  ->onDelete('cascade');
            
            $table->foreign('allocated_by', 'swim_txn_alloc_user_fk')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Add indexes with shorter names (MySQL limit is 64 chars)
            $table->index(['bank_statement_transaction_id', 'student_id'], 'swim_txn_alloc_txn_student_idx');
            $table->index('student_id', 'swim_txn_alloc_student_idx');
            $table->index('status', 'swim_txn_alloc_status_idx');
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
