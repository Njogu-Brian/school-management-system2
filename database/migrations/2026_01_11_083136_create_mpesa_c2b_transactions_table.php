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
        Schema::create('mpesa_c2b_transactions', function (Blueprint $table) {
            $table->id();
            
            // M-PESA transaction details
            $table->string('transaction_type', 50); // C2B, Paybill
            $table->string('trans_id')->unique()->index(); // M-PESA transaction ID
            $table->string('trans_time'); // Transaction timestamp from M-PESA
            $table->decimal('trans_amount', 12, 2);
            $table->string('business_short_code', 20);
            $table->string('bill_ref_number')->nullable()->index(); // Reference number / Account number
            $table->string('invoice_number')->nullable()->index();
            $table->string('org_account_balance')->nullable();
            $table->string('third_party_trans_id')->nullable();
            $table->string('msisdn', 20); // Phone number
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            
            // Smart matching and allocation
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->enum('allocation_status', ['unallocated', 'auto_matched', 'manually_allocated', 'duplicate'])->default('unallocated')->index();
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->decimal('unallocated_amount', 12, 2)->nullable();
            
            // Smart matching metadata
            $table->json('matching_suggestions')->nullable(); // Array of possible student matches
            $table->integer('match_confidence')->nullable(); // 0-100
            $table->string('match_reason')->nullable(); // What caused the match
            
            // Duplicate detection
            $table->boolean('is_duplicate')->default(false)->index();
            $table->foreignId('duplicate_of')->nullable()->constrained('mpesa_c2b_transactions')->onDelete('set null');
            
            // Processing status
            $table->enum('status', ['pending', 'processed', 'failed', 'ignored'])->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            
            // Raw webhook data
            $table->json('raw_data')->nullable();
            
            $table->timestamps();
            
            // Indexes for quick searching
            $table->index('created_at');
            $table->index('trans_time');
            $table->index(['allocation_status', 'status']);
            $table->index(['student_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_c2b_transactions');
    }
};
