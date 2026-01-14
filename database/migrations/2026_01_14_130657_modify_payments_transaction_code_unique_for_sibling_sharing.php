<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change transaction_code unique constraint to allow same code for different students (sibling sharing)
     */
    public function up(): void
    {
        // Drop the existing unique constraint on transaction_code
        if (Schema::hasColumn('payments', 'transaction_code')) {
            $hasUnique = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_transaction_code_unique' OR (Column_name = 'transaction_code' AND Non_unique = 0)");
            if (!empty($hasUnique)) {
                try {
                    DB::statement('ALTER TABLE payments DROP INDEX payments_transaction_code_unique');
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }
            }
        }
        
        // Add composite unique constraint on (transaction_code, student_id)
        // This allows siblings to share the same transaction code, but each student can only have one payment with that code
        if (Schema::hasColumn('payments', 'transaction_code') && Schema::hasColumn('payments', 'student_id')) {
            $hasCompositeUnique = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_transaction_code_student_id_unique'");
            if (empty($hasCompositeUnique)) {
                try {
                    DB::statement('ALTER TABLE payments ADD UNIQUE KEY payments_transaction_code_student_id_unique (transaction_code, student_id)');
                } catch (\Exception $e) {
                    // Log error but don't fail migration
                    \Log::warning('Failed to add composite unique constraint: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop composite unique constraint
        if (Schema::hasColumn('payments', 'transaction_code') && Schema::hasColumn('payments', 'student_id')) {
            $hasCompositeUnique = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_transaction_code_student_id_unique'");
            if (!empty($hasCompositeUnique)) {
                try {
                    DB::statement('ALTER TABLE payments DROP INDEX payments_transaction_code_student_id_unique');
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }
            }
        }
        
        // Restore original unique constraint on transaction_code
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
    }
};
