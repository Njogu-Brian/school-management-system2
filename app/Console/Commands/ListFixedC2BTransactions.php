<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;

class ListFixedC2BTransactions extends Command
{
    protected $signature = 'c2b:list-fixed {--limit=10 : Number of transactions to show}';
    protected $description = 'List C2B transactions that have been fixed (status = processed with payment_id)';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info('Recently fixed C2B transactions (status = processed with payment_id):');
        $this->newLine();
        
        $transactions = MpesaC2BTransaction::where('status', 'processed')
            ->whereNotNull('payment_id')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
        
        if ($transactions->isEmpty()) {
            $this->warn('No fixed transactions found.');
            return 0;
        }
        
        $data = [];
        foreach ($transactions as $txn) {
            $payment = Payment::find($txn->payment_id);
            $data[] = [
                'id' => $txn->id,
                'trans_id' => $txn->trans_id,
                'amount' => 'KES ' . number_format($txn->trans_amount, 2),
                'payment_id' => $txn->payment_id,
                'receipt_number' => $payment ? ($payment->receipt_number ?? $payment->transaction_code ?? 'N/A') : 'N/A',
                'updated_at' => $txn->updated_at->format('Y-m-d H:i:s'),
            ];
        }
        
        $this->table(
            ['Transaction ID', 'Trans ID', 'Amount', 'Payment ID', 'Receipt Number', 'Last Updated'],
            $data
        );
        
        $this->newLine();
        $this->info("Showing {$transactions->count()} transaction(s).");
        
        return 0;
    }
}
