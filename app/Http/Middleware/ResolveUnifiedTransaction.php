<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use Symfony\Component\HttpFoundation\Response;

class ResolveUnifiedTransaction
{
    /**
     * Handle an incoming request and resolve transaction from either type
     */
    public function handle(Request $request, Closure $next): Response
    {
        $transactionId = $request->route('bankStatement') ?? $request->route('transaction');
        
        if ($transactionId) {
            // Try to resolve as BankStatementTransaction first
            $transaction = BankStatementTransaction::find($transactionId);
            
            if (!$transaction) {
                // Try as C2B transaction
                $transaction = MpesaC2BTransaction::find($transactionId);
            }
            
            if ($transaction) {
                // Store in request for later use
                $request->attributes->set('unified_transaction', $transaction);
                $request->attributes->set('transaction_type', $transaction instanceof MpesaC2BTransaction ? 'c2b' : 'bank');
            }
        }
        
        return $next($request);
    }
}
