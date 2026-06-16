<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\FiscalPeriod;
use App\Models\PettyCashFund;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Cash on Hand', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'is_postable' => true, 'is_system' => true],
            ['code' => '1010', 'name' => 'Bank Accounts', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'is_postable' => false, 'is_system' => true],
            ['code' => '1011', 'name' => 'Main Operating Bank', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'parent_code' => '1010', 'is_postable' => true, 'is_system' => true],
            ['code' => '1100', 'name' => 'Petty Cash', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'is_postable' => false, 'is_system' => true],
            ['code' => '1101', 'name' => 'General Petty Cash', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'parent_code' => '1100', 'is_postable' => true, 'is_system' => true],
            ['code' => '2000', 'name' => 'Accounts Payable', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],
            ['code' => '2100', 'name' => 'Salaries Payable', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],
            ['code' => '3000', 'name' => 'Retained Earnings', 'account_type' => Account::TYPE_EQUITY, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],
            ['code' => '4000', 'name' => 'School Fees Income', 'account_type' => Account::TYPE_REVENUE, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],
            ['code' => '5000', 'name' => 'Operating Expenses', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'is_postable' => false, 'is_system' => true],
            ['code' => '5100', 'name' => 'Fuel & Transport', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => false, 'is_system' => true],
            ['code' => '5101', 'name' => 'Fuel', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true, 'is_system' => true],
            ['code' => '5102', 'name' => 'Vehicle Maintenance', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true, 'is_system' => true],
            ['code' => '5200', 'name' => 'Payroll & Staff', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5300', 'name' => 'Utilities', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => false, 'is_system' => true],
            ['code' => '5301', 'name' => 'Internet & Wi-Fi', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true, 'is_system' => true],
            ['code' => '5302', 'name' => 'Phone Bills', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true, 'is_system' => true],
            ['code' => '5400', 'name' => 'Office & Supplies', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5500', 'name' => 'Food & Catering', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5600', 'name' => 'Construction & Repairs', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5700', 'name' => 'Transaction Costs', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5999', 'name' => 'Miscellaneous Expense', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
        ];

        $created = [];

        foreach ($accounts as $row) {
            $parentId = isset($row['parent_code']) ? ($created[$row['parent_code']] ?? null) : null;
            unset($row['parent_code']);

            $account = Account::updateOrCreate(
                ['code' => $row['code']],
                array_merge($row, ['parent_id' => $parentId, 'is_active' => true])
            );

            $created[$account->code] = $account->id;
        }

        $this->seedExpenseCategoryTree($created);

        $pettyCashAccountId = $created['1101'] ?? null;
        if ($pettyCashAccountId) {
            PettyCashFund::updateOrCreate(
                ['code' => 'PC-GEN'],
                [
                    'name' => 'General Petty Cash',
                    'account_id' => $pettyCashAccountId,
                    'custodian_id' => User::query()->orderBy('id')->value('id'),
                    'imprest_amount' => 10000,
                    'is_active' => true,
                ]
            );
        }

        FiscalPeriod::updateOrCreate(
            ['name' => 'FY ' . now()->year],
            [
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'status' => FiscalPeriod::STATUS_OPEN,
            ]
        );
    }

    /**
     * @param  array<string, int>  $accounts
     */
    protected function seedExpenseCategoryTree(array $accounts): void
    {
        $tree = [
            ['code' => 'FUEL', 'name' => 'Fuel & Transport', 'is_header' => true, 'children' => [
                ['code' => 'FUEL-DIESEL', 'name' => 'Diesel', 'account_code' => '5101'],
                ['code' => 'FUEL-PETROL', 'name' => 'Petrol', 'account_code' => '5101'],
                ['code' => 'MOTOR_MAINT', 'name' => 'Vehicle Maintenance', 'account_code' => '5102'],
            ]],
            ['code' => 'UTILITIES', 'name' => 'Utilities', 'is_header' => true, 'children' => [
                ['code' => 'INTERNET', 'name' => 'Internet', 'account_code' => '5301'],
                ['code' => 'WIFI', 'name' => 'Wi-Fi', 'account_code' => '5301'],
                ['code' => 'PHONE', 'name' => 'Phone Bill', 'account_code' => '5302'],
            ]],
            ['code' => 'PAYROLL', 'name' => 'Payroll', 'account_code' => '5200'],
            ['code' => 'FOOD', 'name' => 'Food & Catering', 'account_code' => '5500'],
            ['code' => 'OFFICE', 'name' => 'Office Expenses', 'account_code' => '5400'],
            ['code' => 'CONSTRUCTION', 'name' => 'Construction', 'account_code' => '5600'],
            ['code' => 'REPAIRS', 'name' => 'Repairs', 'account_code' => '5600'],
            ['code' => 'MAINTENANCE', 'name' => 'Maintenance', 'account_code' => '5600'],
            ['code' => 'TXN_COST', 'name' => 'Transaction Costs', 'account_code' => '5700'],
            ['code' => 'WITHDRAWAL', 'name' => 'Withdrawal Charges', 'account_code' => '5700'],
        ];

        foreach ($tree as $node) {
            $this->upsertCategoryNode($node, null, $accounts);
        }
    }

    /**
     * @param  array<string, int>  $accounts
     */
    protected function upsertCategoryNode(array $node, ?int $parentId, array $accounts): void
    {
        $accountId = isset($node['account_code']) ? ($accounts[$node['account_code']] ?? null) : null;

        $category = ExpenseCategory::updateOrCreate(
            ['code' => $node['code']],
            [
                'name' => $node['name'],
                'parent_id' => $parentId,
                'account_id' => $accountId,
                'is_header' => (bool) ($node['is_header'] ?? false),
                'is_active' => true,
            ]
        );

        foreach ($node['children'] ?? [] as $child) {
            $this->upsertCategoryNode($child, $category->id, $accounts);
        }
    }
}
