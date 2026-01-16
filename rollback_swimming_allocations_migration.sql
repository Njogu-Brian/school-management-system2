-- Manual rollback script for swimming_transaction_allocations migration
-- Run this in your database if the table doesn't exist but migration shows as "Ran"

-- Step 1: Remove the migration record from migrations table
DELETE FROM migrations 
WHERE migration = '2026_01_15_084857_create_swimming_transaction_allocations_table';

-- Step 2: Verify the record is removed
SELECT * FROM migrations 
WHERE migration LIKE '%swimming_transaction_allocations%';

-- After running this, you can re-run: php artisan migrate
