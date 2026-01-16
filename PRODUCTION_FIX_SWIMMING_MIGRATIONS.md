# Production Fix for Swimming Migrations

## Problem
The `swimming_ledger` migration (083821) was trying to create a foreign key to `swimming_attendance` table, but `swimming_attendance` was created later (084825), causing a foreign key constraint error.

## Solution

### Option 1: PHP Script (Recommended)
Run the PHP fix script on your production server:

```bash
php fix_swimming_migrations_production.php
```

This script will:
1. Create `swimming_attendance` table if it doesn't exist
2. Create or fix `swimming_ledger` table with proper foreign key
3. Update the migrations table to mark all swimming migrations as run

### Option 2: SQL Script
If the PHP script doesn't work, you can run the SQL directly:

1. Connect to your production database
2. Run the SQL commands from `fix_swimming_migrations_production.sql`

**Important:** Before running the SQL, check if `swimming_ledger` table exists:
- If it exists but is broken (missing foreign key), you may need to drop it first:
  ```sql
  DROP TABLE IF EXISTS `swimming_ledger`;
  ```
- Then run the CREATE TABLE statements from the SQL file

### Option 3: Manual Fix via cPanel/phpMyAdmin

1. **Create swimming_attendance table first:**
   - Go to phpMyAdmin or your database tool
   - Run the CREATE TABLE statement for `swimming_attendance` from the SQL file

2. **Fix swimming_ledger table:**
   - If the table doesn't exist, create it using the CREATE TABLE statement
   - If it exists but is missing the foreign key, add it:
     ```sql
     ALTER TABLE swimming_ledger 
     ADD CONSTRAINT swimming_ledger_swimming_attendance_id_foreign 
     FOREIGN KEY (swimming_attendance_id) 
     REFERENCES swimming_attendance(id) 
     ON DELETE SET NULL;
     ```

3. **Update migrations table:**
   - Insert the migration records (note: attendance migration is renamed to 083815):
     ```sql
     INSERT IGNORE INTO migrations (migration, batch) VALUES
     ('2026_01_15_083721_create_swimming_wallets_table', [next_batch]),
     ('2026_01_15_083815_create_swimming_attendance_table', [next_batch]),
     ('2026_01_15_083821_create_swimming_ledger_table', [next_batch]),
     ('2026_01_15_084857_create_swimming_transaction_allocations_table', [next_batch]),
     ('2026_01_15_084913_add_swimming_fields_to_bank_statement_transactions_table', [next_batch]);
     ```
   - Replace `[next_batch]` with the next batch number (check your migrations table for the highest batch number and add 1)

## After Fixing

1. **Verify tables exist:**
   ```sql
   SHOW TABLES LIKE 'swimming_%';
   ```

2. **Check foreign keys:**
   ```sql
   SELECT 
     CONSTRAINT_NAME, 
     TABLE_NAME, 
     COLUMN_NAME, 
     REFERENCED_TABLE_NAME, 
     REFERENCED_COLUMN_NAME 
   FROM information_schema.KEY_COLUMN_USAGE 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'swimming_ledger' 
   AND CONSTRAINT_NAME = 'swimming_ledger_swimming_attendance_id_foreign';
   ```

3. **Run remaining migrations:**
   ```bash
   php artisan migrate
   ```

## Local Development

The migration file has been renamed locally:
- `2026_01_15_084825_create_swimming_attendance_table.php` → `2026_01_15_083815_create_swimming_attendance_table.php`

This ensures the correct order for future deployments.

## Notes

- The `swimming_attendance` migration timestamp was changed from `084825` to `083815` to run before `swimming_ledger` (083821)
- All swimming-related migrations should now run in the correct order
- The migrations table will be updated to reflect the corrected order
