<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function reverse(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
}
