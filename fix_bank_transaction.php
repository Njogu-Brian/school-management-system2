<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNOSTIC TOOL FOR BANK TRANSACTIONS ===\n\n";

// First, let's see if we can query the table at all
$totalTransactions = \App\Models\BankStatementTransaction::count();
echo "Total bank statement transactions: {$totalTransactions}\n\n";

if ($totalTransactions == 0) {
    echo "❌ No transactions found in the table!\n";
    exit;
}

// Show latest 10 transactions
echo "Latest 10 transactions:\n";
$latest = \App\Models\BankStatementTransaction::orderBy('id', 'desc')->limit(10)->get();
foreach ($latest as $t) {
    $statusDisplay = is_string($t->status) ? $t->status : (is_bool($t->payment_created) ? ($t->payment_created ? 'Collected' : 'Not Collected') : 'Unknown');
    echo "  - ID: {$t->id}, Amount: Ksh {$t->amount}, Date: {$t->transaction_date}, Status: {$statusDisplay}\n";
    echo "    payment_created: " . json_encode($t->payment_created) . ", payment_id: " . ($t->payment_id ?? 'NULL') . "\n";
    echo "    Description: " . substr($t->description ?? 'N/A', 0, 80) . "\n\n";
}

$transaction = null;

// Look for transactions dated around Jan 6 with large amounts
echo "\n\nSearching for transaction from Jan 6 with amount around Ksh 68,700...\n";
$transaction = \App\Models\BankStatementTransaction::whereDate('transaction_date', '2026-01-06')
    ->whereBetween('amount', [68000, 69000])
    ->orderBy('id', 'desc')
    ->first();

if ($transaction) {
    echo "Found transaction:\n";
    echo "ID: {$transaction->id}\n";
    echo "Amount: Ksh {$transaction->amount}\n";
    echo "Current Status: {$transaction->status}\n";
    echo "Payment Created: " . ($transaction->payment_created ? 'Yes' : 'No') . "\n";
    echo "Payment ID: " . ($transaction->payment_id ?? 'NULL') . "\n";
    echo "\nUpdating...\n";
    
    $transaction->update([
        'payment_created' => false,
        'payment_id' => null,
        'status' => 'draft'
    ]);
    
    echo "\n✅ Transaction updated successfully!\n";
    echo "New Status: {$transaction->status}\n";
    echo "Payment Created: " . ($transaction->payment_created ? 'Yes' : 'No') . "\n";
    echo "\nYou can now refresh the Bank Statements page.\n";
} else {
    echo "\n❌ No matching transaction found!\n";
    echo "\nLet's check what transactions exist for amount Ksh 68,700...\n";
    
    // Try both integer and decimal formats
    $allMatching = \App\Models\BankStatementTransaction::where(function($q) {
        $q->where('amount', 68700)
          ->orWhere('amount', 68700.00)
          ->orWhereBetween('amount', [68699, 68701]);
    })->get();
    
    echo "Found " . $allMatching->count() . " transactions with this amount:\n";
    foreach ($allMatching as $t) {
        echo "  - ID: {$t->id}, Amount: {$t->amount}, Student: " . ($t->student_id ?? 'NULL') . ", Status: {$t->status}, Payment Created: " . ($t->payment_created ? 'Yes' : 'No') . ", Payment ID: " . ($t->payment_id ?? 'NULL') . ", Date: {$t->transaction_date}\n";
    }
    
    // Also show some recent transactions with payment_created = true
    echo "\n\nRecent 'Collected' transactions:\n";
    $collected = \App\Models\BankStatementTransaction::where('payment_created', true)
        ->orderBy('transaction_date', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($collected as $t) {
        echo "  - ID: {$t->id}, Amount: Ksh {$t->amount}, Student: " . ($t->student_id ?? 'NULL') . ", Status: {$t->status}, Payment ID: " . ($t->payment_id ?? 'NULL') . "\n";
        echo "    Description: " . substr($t->description, 0, 60) . "...\n";
    }
}

