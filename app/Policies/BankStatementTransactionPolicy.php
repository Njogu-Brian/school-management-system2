<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BankStatementTransaction;

class BankStatementTransactionPolicy
{
    /**
     * Determine if user can reject a transaction
     */
    public function reject(User $user, BankStatementTransaction $transaction): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer']);
    }
    
    /**
     * Determine if user can archive a transaction
     */
    public function archive(User $user, BankStatementTransaction $transaction): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer']);
    }
    
    /**
     * Determine if user can edit allocations for a transaction
     */
    public function editAllocations(User $user, BankStatementTransaction $transaction): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
    
    /**
     * Determine if user can confirm a transaction
     */
    public function confirm(User $user, BankStatementTransaction $transaction): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
    
    /**
     * Determine if user can view transaction details
     */
    public function view(User $user, BankStatementTransaction $transaction): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant']);
    }
}
