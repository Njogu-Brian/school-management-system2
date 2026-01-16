#!/bin/bash
# Complete fix for swimming_transaction_allocations migration
# This script handles the partial table creation and re-runs with fixed migration

echo "=========================================="
echo "Complete Fix for swimming_transaction_allocations"
echo "=========================================="
echo ""

# Step 1: Pull latest code
echo "Step 1: Pulling latest code from GitHub..."
git pull
echo ""

# Step 2: Drop the partially created table (if it exists)
echo "Step 2: Dropping partially created table (if exists)..."
php artisan tinker --execute="
if (Schema::hasTable('swimming_transaction_allocations')) {
    Schema::dropIfExists('swimming_transaction_allocations');
    echo 'Table dropped';
} else {
    echo 'Table does not exist (nothing to drop)';
}
"
echo ""

# Step 3: Remove migration record
echo "Step 3: Removing migration record from migrations table..."
php artisan tinker --execute="
DB::table('migrations')
    ->where('migration', '2026_01_15_084857_create_swimming_transaction_allocations_table')
    ->delete();
echo 'Migration record removed';
"
echo ""

# Step 4: Re-run the migration with fixed constraint names
echo "Step 4: Re-running migration with fixed constraint names..."
php artisan migrate --path=database/migrations/2026_01_15_084857_create_swimming_transaction_allocations_table.php
echo ""

# Step 5: Verify table was created with all constraints
echo "Step 5: Verifying table and constraints..."
php artisan tinker --execute="
if (Schema::hasTable('swimming_transaction_allocations')) {
    echo '✅ Table exists';
    \$constraints = DB::select('SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \"swimming_transaction_allocations\" AND CONSTRAINT_NAME LIKE \"%fk%\"');
    echo 'Foreign key constraints: ' . count(\$constraints);
    foreach (\$constraints as \$constraint) {
        echo '  - ' . \$constraint->CONSTRAINT_NAME;
    }
} else {
    echo '❌ Table does not exist';
}
"

echo ""
echo "=========================================="
echo "✅ Fix complete!"
echo "=========================================="
