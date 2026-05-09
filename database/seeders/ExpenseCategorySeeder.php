<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'TXN_COST', 'name' => 'Transaction Costs'],
            ['code' => 'WITHDRAWAL', 'name' => 'Withdrawal Charges'],
            ['code' => 'FUEL', 'name' => 'Fuel'],
            ['code' => 'MOTOR_MAINT', 'name' => 'Motor Vehicle Maintenance'],
            ['code' => 'PAYROLL', 'name' => 'Payroll'],
            ['code' => 'FOOD', 'name' => 'Food'],
            ['code' => 'OFFICE', 'name' => 'Office Expenses'],
            ['code' => 'INTERNET', 'name' => 'Internet'],
            ['code' => 'WIFI', 'name' => 'Wi-Fi'],
            ['code' => 'PHONE', 'name' => 'Phone Bill'],
            ['code' => 'CONSTRUCTION', 'name' => 'Construction'],
            ['code' => 'REPAIRS', 'name' => 'Repairs'],
            ['code' => 'MAINTENANCE', 'name' => 'Maintenance'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['code' => $category['code']],
                ['name' => $category['name'], 'is_active' => true]
            );
        }
    }
}
