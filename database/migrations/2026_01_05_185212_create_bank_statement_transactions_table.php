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
        Schema::create('bank_statement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            $table->string('statement_file_path')->nullable(); // Path to uploaded PDF
            $table->string('bank_type')->nullable(); // 'mpesa' or 'equity'
            
            // Transaction details from statement
            $table->date('transaction_date');
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', ['credit', 'debit'])->default('credit');
            $table->string('reference_number')->nullable(); // Transaction reference from bank
            $table->text('description')->nullable(); // Full description from statement
            $table->string('phone_number')->nullable(); // Extracted phone number (for MPESA)
            $table->string('matched_admission_number')->nullable(); // Matched student admission number
            $table->string('matched_student_name')->nullable(); // Matched student name
            $table->string('matched_phone_number')->nullable(); // Matched parent/guardian phone
            
            // Matching and assignment
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->foreignId('family_id')->nullable()->constrained('families')->onDelete('set null');
            $table->enum('match_status', ['unmatched', 'matched', 'multiple_matches', 'manual'])->default('unmatched');
            $table->decimal('match_confidence', 3, 2)->nullable(); // 0.00 to 1.00
            $table->text('match_notes')->nullable();
            
            // Transaction status
            $table->enum('status', ['draft', 'confirmed', 'rejected'])->default('draft');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            
            // Payment creation
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->boolean('payment_created')->default(false);
            
            // Sharing/Transfer
            $table->boolean('is_shared')->default(false);
            $table->text('shared_allocations')->nullable(); // JSON: [{"student_id": X, "amount": Y}, ...]
            
            // Additional metadata
            $table->json('raw_data')->nullable(); // Full parsed data from statement
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->index('transaction_date');
            $table->index('status');
            $table->index('match_status');
            $table->index('student_id');
            $table->index('bank_account_id');
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_transactions');
    }
};
