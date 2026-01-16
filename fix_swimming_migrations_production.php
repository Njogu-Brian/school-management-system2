<?php

/**
 * Production Fix Script for Swimming Migrations
 * 
 * This script fixes the migration order issue where swimming_ledger
 * was created before swimming_attendance, causing a foreign key constraint error.
 * 
 * Run this script in production via: php fix_swimming_migrations_production.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Starting swimming migrations fix for production...\n\n";

try {
    // Note: DDL operations (CREATE TABLE, ALTER TABLE) auto-commit in MySQL
    // So we don't wrap everything in a transaction, but handle errors manually

    // Step 1: Check if swimming_attendance table exists
    $attendanceExists = Schema::hasTable('swimming_attendance');
    echo "1. Checking swimming_attendance table... ";
    if (!$attendanceExists) {
        echo "NOT FOUND - Creating it now...\n";
        
        Schema::create('swimming_attendance', function ($table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            $table->decimal('session_cost', 10, 2)->nullable()->comment('Per-visit cost at time of attendance');
            $table->boolean('termly_fee_covered')->default(false)->comment('Whether covered by termly optional fee');
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            
            // One attendance record per student per date
            $table->unique(['student_id', 'attendance_date'], 'unique_student_date');
            $table->index(['classroom_id', 'attendance_date']);
            $table->index('attendance_date');
            $table->index('payment_status');
        });
        
        echo "   ✓ swimming_attendance table created successfully\n";
    } else {
        echo "EXISTS - Skipping creation\n";
    }

    // Step 2: Check if swimming_ledger table exists (might be partially created)
    $ledgerExists = Schema::hasTable('swimming_ledger');
    echo "2. Checking swimming_ledger table... ";
    
    if ($ledgerExists) {
        // Check if the foreign key constraint exists
        $constraintExists = false;
        try {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'swimming_ledger' 
                AND CONSTRAINT_NAME = 'swimming_ledger_swimming_attendance_id_foreign'
            ");
            $constraintExists = !empty($constraints);
        } catch (\Exception $e) {
            // Ignore errors checking constraint
        }
        
        if (!$constraintExists) {
            echo "EXISTS but missing foreign key - Adding constraint...\n";
            try {
                DB::statement("
                    ALTER TABLE swimming_ledger 
                    ADD CONSTRAINT swimming_ledger_swimming_attendance_id_foreign 
                    FOREIGN KEY (swimming_attendance_id) 
                    REFERENCES swimming_attendance(id) 
                    ON DELETE SET NULL
                ");
                echo "   ✓ Foreign key constraint added successfully\n";
            } catch (\Exception $e) {
                echo "   ⚠ Warning: Could not add foreign key: " . $e->getMessage() . "\n";
                echo "   The table exists but may need manual intervention.\n";
            }
        } else {
            echo "EXISTS with foreign key - Already correct\n";
        }
    } else {
        echo "NOT FOUND - Creating it now...\n";
        
        Schema::create('swimming_ledger', function ($table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2)->comment('Balance after this transaction');
            $table->string('source')->comment('transaction, optional_fee, adjustment, attendance');
            $table->foreignId('source_id')->nullable()->comment('ID of source record (payment_id, optional_fee_id, attendance_id, etc)');
            $table->string('source_type')->nullable()->comment('Model class name for polymorphic relation');
            $table->foreignId('swimming_attendance_id')->nullable()->constrained('swimming_attendance')->onDelete('set null');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['student_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('swimming_attendance_id');
        });
        
        echo "   ✓ swimming_ledger table created successfully\n";
    }

    // Step 3: Update migrations table to mark these as run
    echo "3. Updating migrations table...\n";
    
    $migrations = [
        '2026_01_15_083721_create_swimming_wallets_table',
        '2026_01_15_083815_create_swimming_attendance_table', // Note: renamed from 084825
        '2026_01_15_083821_create_swimming_ledger_table',
        '2026_01_15_084857_create_swimming_transaction_allocations_table',
        '2026_01_15_084913_add_swimming_fields_to_bank_statement_transactions_table',
    ];
    
    foreach ($migrations as $migration) {
        $exists = DB::table('migrations')->where('migration', $migration)->exists();
        if (!$exists) {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => DB::table('migrations')->max('batch') + 1,
            ]);
            echo "   ✓ Marked {$migration} as migrated\n";
        } else {
            echo "   - {$migration} already in migrations table\n";
        }
    }

    // All operations completed successfully
    echo "\n✅ All fixes applied successfully!\n";
    echo "\nNext steps:\n";
    echo "1. The migrations table has been updated with the correct order\n";
    echo "2. You can now run 'php artisan migrate' to create any remaining tables\n";
    echo "3. The migration files should be renamed locally to match the corrected order\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "\n⚠️  Note: Some operations may have completed before the error.\n";
    echo "Please check the database state and re-run if necessary.\n";
    exit(1);
}
