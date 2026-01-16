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
        // Check if columns exist before adding (allows safe re-running)
        if (!Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            Schema::table('bank_statement_transactions', function (Blueprint $table) {
                $table->boolean('is_swimming_transaction')->default(false)->after('is_shared')->comment('Marked as swimming payment, excluded from fee allocation');
            });
        }
        
        if (!Schema::hasColumn('bank_statement_transactions', 'swimming_allocated_amount')) {
            Schema::table('bank_statement_transactions', function (Blueprint $table) {
                $table->decimal('swimming_allocated_amount', 10, 2)->default(0)->after('is_swimming_transaction')->comment('Total amount allocated to swimming');
            });
        }
        
        // Add index if column exists and index doesn't exist
        if (Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            $indexExists = false;
            try {
                $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('bank_statement_transactions');
                foreach ($indexes as $index) {
                    if (in_array('is_swimming_transaction', $index->getColumns())) {
                        $indexExists = true;
                        break;
                    }
                }
            } catch (\Exception $e) {
                // If we can't check indexes, try to add it anyway (will fail gracefully if exists)
            }
            
            if (!$indexExists) {
                Schema::table('bank_statement_transactions', function (Blueprint $table) {
                    $table->index('is_swimming_transaction');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop if columns exist
        if (Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            Schema::table('bank_statement_transactions', function (Blueprint $table) {
                // Try to drop index (may not exist)
                try {
                    $table->dropIndex(['is_swimming_transaction']);
                } catch (\Exception $e) {
                    // Index doesn't exist, continue
                }
            });
        }
        
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
                $table->dropColumn('is_swimming_transaction');
            }
            if (Schema::hasColumn('bank_statement_transactions', 'swimming_allocated_amount')) {
                $table->dropColumn('swimming_allocated_amount');
            }
        });
    }
};
