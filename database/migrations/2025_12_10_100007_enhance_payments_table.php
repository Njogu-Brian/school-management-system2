<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (!Schema::hasColumn('payments', 'transaction_code')) {
                $table->string('transaction_code')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('payments', 'family_id')) {
                $table->foreignId('family_id')->nullable()->after('student_id')->constrained('families')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('invoice_id')->constrained('bank_accounts')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('payment_method')->constrained('payment_methods')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'allocated_amount')) {
                $table->decimal('allocated_amount', 10, 2)->default(0)->after('amount');
            }
            
            if (!Schema::hasColumn('payments', 'unallocated_amount')) {
                $table->decimal('unallocated_amount', 10, 2)->default(0)->after('allocated_amount');
            }
            
            if (!Schema::hasColumn('payments', 'payer_name')) {
                $table->string('payer_name')->nullable()->after('unallocated_amount');
            }
            
            if (!Schema::hasColumn('payments', 'payer_type')) {
                $table->enum('payer_type', ['parent', 'sponsor', 'student', 'other'])->nullable()->after('payer_name');
            }
            
            if (!Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('transaction_code');
            }
            
            if (!Schema::hasColumn('payments', 'narration')) {
                $table->text('narration')->nullable()->after('payer_type');
            }
            
            if (!Schema::hasColumn('payments', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed')->constrained('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            }
        });
        
        // Add indexes (only if columns exist and indexes don't)
        Schema::table('payments', function (Blueprint $table) {
            $hasTransactionCodeIndex = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_transaction_code_index'");
            if (empty($hasTransactionCodeIndex) && Schema::hasColumn('payments', 'transaction_code')) {
                $table->index('transaction_code');
            }
            
            $hasReceiptNumberIndex = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_receipt_number_index'");
            if (empty($hasReceiptNumberIndex) && Schema::hasColumn('payments', 'receipt_number')) {
                $table->index('receipt_number');
            }
        });
        
        // Add unique constraints separately (check if they exist first)
        if (Schema::hasColumn('payments', 'transaction_code')) {
            $hasUnique = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_transaction_code_unique' OR (Column_name = 'transaction_code' AND Non_unique = 0)");
            if (empty($hasUnique)) {
                try {
                    DB::statement('ALTER TABLE payments ADD UNIQUE KEY payments_transaction_code_unique (transaction_code)');
                } catch (\Exception $e) {
                    // Ignore if constraint already exists
                }
            }
        }
        
        if (Schema::hasColumn('payments', 'receipt_number')) {
            $hasUnique = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_receipt_number_unique' OR (Column_name = 'receipt_number' AND Non_unique = 0)");
            if (empty($hasUnique)) {
                try {
                    DB::statement('ALTER TABLE payments ADD UNIQUE KEY payments_receipt_number_unique (receipt_number)');
                } catch (\Exception $e) {
                    // Ignore if constraint already exists
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['reversed_by']);
            
            $table->dropUnique(['transaction_code']);
            $table->dropUnique(['receipt_number']);
            
            $table->dropIndex(['transaction_code']);
            $table->dropIndex(['receipt_number']);
            
            $table->dropColumn([
                'transaction_code', 'family_id', 'bank_account_id', 'payment_method_id',
                'allocated_amount', 'unallocated_amount',
                'payer_name', 'payer_type', 'receipt_number',
                'narration', 'reversed_by', 'reversed_at'
            ]);
        });
    }
};

