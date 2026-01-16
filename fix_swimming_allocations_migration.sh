#!/bin/bash
# Fix swimming_transaction_allocations migration
# This script removes the migration record and re-runs it

echo "=========================================="
echo "Fixing swimming_transaction_allocations migration"
echo "=========================================="
echo ""

# Step 1: Check if table exists
echo "Step 1: Checking if table exists..."
php artisan tinker --execute="echo Schema::hasTable('swimming_transaction_allocations') ? 'Table EXISTS' : 'Table DOES NOT EXIST';"

# Step 2: Remove migration record if table doesn't exist
echo ""
echo "Step 2: Removing migration record from migrations table..."
php artisan tinker --execute="
DB::table('migrations')
    ->where('migration', '2026_01_15_084857_create_swimming_transaction_allocations_table')
    ->delete();
echo 'Migration record removed';
"

# Step 3: Re-run the migration
echo ""
echo "Step 3: Re-running migration..."
php artisan migrate --path=database/migrations/2026_01_15_084857_create_swimming_transaction_allocations_table.php

# Step 4: Verify table was created
echo ""
echo "Step 4: Verifying table was created..."
php artisan tinker --execute="echo Schema::hasTable('swimming_transaction_allocations') ? '✅ Table created successfully!' : '❌ Table still does not exist';"

echo ""
echo "=========================================="
echo "✅ Migration fix complete!"
echo "=========================================="
