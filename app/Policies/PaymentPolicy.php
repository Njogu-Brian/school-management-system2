<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payment;

class PaymentPolicy
{
    /**
     * Determine if user can reverse a payment
     */
    public function reverse(User $user, Payment $payment): bool
    {
        // Only Finance Officer, Accountant, Admin can reverse
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
    
    /**
     * Determine if user can edit shared allocations for a payment
     */
    public function editSharedAllocations(User $user, Payment $payment): bool
    {
        // Only Finance Officer, Accountant, Admin can edit shared amounts
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
    
    /**
     * Determine if user can transfer a payment
     */
    public function transfer(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
    
    /**
     * Determine if user can view payment details
     */
    public function view(User $user, Payment $payment): bool
    {
        // All authenticated users with finance access can view
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant']);
    }
}
