<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant']);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->id === $expense->requested_by
            || $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant']);
    }

    public function update(User $user, Expense $expense): bool
    {
        if ($expense->status !== Expense::STATUS_DRAFT) {
            return false;
        }

        return $user->id === $expense->requested_by
            || $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }

    public function submit(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $expense->status === Expense::STATUS_SUBMITTED
            && $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }

    public function pay(User $user, Expense $expense): bool
    {
        return $expense->status === Expense::STATUS_APPROVED
            && $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
}
