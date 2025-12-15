<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Main School Account',
                'account_number' => '0000000000',
                'bank_name' => 'Example Bank',
                'branch' => 'Main Branch',
                'account_type' => 'current',
                'is_active' => true,
                'currency' => 'KES',
                'notes' => 'Primary deposit account',
            ],
            [
                'name' => 'Fees Collection Account',
                'account_number' => '1111111111',
                'bank_name' => 'Example Bank',
                'branch' => 'Main Branch',
                'account_type' => 'current',
                'is_active' => true,
                'currency' => 'KES',
                'notes' => 'Account dedicated to fee collections',
            ],
        ];

        foreach ($accounts as $account) {
            BankAccount::updateOrCreate(
                ['account_number' => $account['account_number']],
                $account
            );
        }
    }
}

