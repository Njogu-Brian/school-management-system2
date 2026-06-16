<?php

namespace App\Policies;

use App\Models\ExpenseStatementImport;
use App\Models\User;

class ExpenseStatementImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant']);
    }

    public function view(User $user, ExpenseStatementImport $import): bool
    {
        return $user->id === $import->uploaded_by
            || $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant']);
    }

    public function update(User $user, ExpenseStatementImport $import): bool
    {
        return $this->view($user, $import);
    }

    public function delete(User $user, ExpenseStatementImport $import): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Finance Officer', 'Accountant']);
    }
}
