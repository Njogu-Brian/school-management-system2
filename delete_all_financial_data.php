<?php

/**
 * WARNING: This script will PERMANENTLY DELETE all financial data:
 * - All payments
 * - All payment allocations
 * - All bank statement transactions
 * - All C2B transactions
 * - All swimming wallets and credits
 * - All swimming ledger entries
 * 
 * This action CANNOT be undone!
 * 
 * Run with: php delete_all_financial_data.php --confirm
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check for confirmation flag
$confirm = in_array('--confirm', $argv ?? []);

if (!$confirm) {
    echo "⚠️  WARNING: This will PERMANENTLY DELETE all financial data!\n\n";
    echo "This includes:\n";
    echo "  - All payments\n";
    echo "  - All payment allocations\n";
    echo "  - All bank statement transactions\n";
    echo "  - All C2B transactions\n";
    echo "  - All swimming wallets and credits\n";
    echo "  - All swimming ledger entries\n";
    echo "  - All payment transactions\n\n";
    echo "This action CANNOT be undone!\n\n";
    echo "To proceed, run: php delete_all_financial_data.php --confirm\n";
    exit(1);
}

echo "Starting deletion process...\n\n";

use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    $counts = [];
    
    // 1. Delete payment allocations (references payments and invoice_items)
    echo "1. Deleting payment allocations...\n";
    $counts['payment_allocations'] = DB::table('payment_allocations')->count();
    DB::table('payment_allocations')->delete();
    echo "   ✓ Deleted {$counts['payment_allocations']} payment allocation(s)\n\n";
    
    // 2. Delete swimming transaction allocations (if table exists)
    if (DB::getSchemaBuilder()->hasTable('swimming_transaction_allocations')) {
        echo "2. Deleting swimming transaction allocations...\n";
        $counts['swimming_transaction_allocations'] = DB::table('swimming_transaction_allocations')->count();
        DB::table('swimming_transaction_allocations')->delete();
        echo "   ✓ Deleted {$counts['swimming_transaction_allocations']} swimming transaction allocation(s)\n\n";
    }
    
    // 3. Delete swimming ledger entries
    if (DB::getSchemaBuilder()->hasTable('swimming_ledger')) {
        echo "3. Deleting swimming ledger entries...\n";
        $counts['swimming_ledger'] = DB::table('swimming_ledger')->count();
        DB::table('swimming_ledger')->delete();
        echo "   ✓ Deleted {$counts['swimming_ledger']} swimming ledger entry/entries\n\n";
    }
    
    // 4. Delete swimming wallets
    if (DB::getSchemaBuilder()->hasTable('swimming_wallets')) {
        echo "4. Deleting swimming wallets...\n";
        $counts['swimming_wallets'] = DB::table('swimming_wallets')->count();
        DB::table('swimming_wallets')->delete();
        echo "   ✓ Deleted {$counts['swimming_wallets']} swimming wallet(s)\n\n";
    }
    
    // 5. Delete bank statement transactions (payment_id will be set to null due to onDelete set null)
    echo "5. Deleting bank statement transactions...\n";
    $counts['bank_statement_transactions'] = DB::table('bank_statement_transactions')->count();
    DB::table('bank_statement_transactions')->delete();
    echo "   ✓ Deleted {$counts['bank_statement_transactions']} bank statement transaction(s)\n\n";
    
    // 6. Delete C2B transactions (payment_id will be set to null due to onDelete set null)
    if (DB::getSchemaBuilder()->hasTable('mpesa_c2b_transactions')) {
        echo "6. Deleting C2B transactions...\n";
        $counts['mpesa_c2b_transactions'] = DB::table('mpesa_c2b_transactions')->count();
        DB::table('mpesa_c2b_transactions')->delete();
        echo "   ✓ Deleted {$counts['mpesa_c2b_transactions']} C2B transaction(s)\n\n";
    }
    
    // 7. Delete payment transactions (if separate table exists)
    if (DB::getSchemaBuilder()->hasTable('payment_transactions')) {
        echo "7. Deleting payment transactions...\n";
        $counts['payment_transactions'] = DB::table('payment_transactions')->count();
        DB::table('payment_transactions')->delete();
        echo "   ✓ Deleted {$counts['payment_transactions']} payment transaction(s)\n\n";
    }
    
    // 8. Delete receipts (if separate table exists)
    if (DB::getSchemaBuilder()->hasTable('receipts')) {
        echo "8. Deleting receipts...\n";
        $counts['receipts'] = DB::table('receipts')->count();
        DB::table('receipts')->delete();
        echo "   ✓ Deleted {$counts['receipts']} receipt(s)\n\n";
    }
    
    // 9. Delete payments (this will cascade/update related records based on foreign keys)
    echo "9. Deleting payments...\n";
    $counts['payments'] = DB::table('payments')->count();
    DB::table('payments')->delete();
    echo "   ✓ Deleted {$counts['payments']} payment(s)\n\n";
    
    // 10. Reset payment-related counters/balances if needed
    echo "10. Resetting invoice balances...\n";
    DB::table('invoices')->update([
        'paid_amount' => 0,
        'balance' => DB::raw('total - discount_amount'),
    ]);
    echo "   ✓ Reset invoice balances\n\n";
    
    // 11. Reset invoice item balances
    if (DB::getSchemaBuilder()->hasTable('invoice_items')) {
        echo "11. Resetting invoice item balances...\n";
        DB::table('invoice_items')->update([
            'paid_amount' => 0,
            'balance' => DB::raw('amount - discount_amount'),
        ]);
        echo "   ✓ Reset invoice item balances\n\n";
    }
    
    DB::commit();
    
    echo "✅ SUCCESS: All financial data has been deleted!\n\n";
    echo "Summary:\n";
    foreach ($counts as $table => $count) {
        echo "  - {$table}: {$count} record(s)\n";
    }
    echo "\nYou can now start fresh with new transactions.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: Deletion failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    exit(1);
}
