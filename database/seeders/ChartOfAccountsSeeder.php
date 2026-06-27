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
            // ---- Assets (1xxx) ----
            ['code' => '1000', 'name' => 'Cash on Hand', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'is_postable' => true, 'is_system' => true],
            ['code' => '1010', 'name' => 'Bank Accounts', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'is_postable' => false, 'is_system' => true],
            ['code' => '1011', 'name' => 'Main Operating Bank', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'parent_code' => '1010', 'is_postable' => true, 'is_system' => true],
            ['code' => '1100', 'name' => 'Petty Cash', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'is_postable' => false, 'is_system' => true],
            ['code' => '1101', 'name' => 'General Petty Cash', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'parent_code' => '1100', 'is_postable' => true, 'is_system' => true],
            ['code' => '1500', 'name' => 'Fixed Assets', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'is_postable' => false, 'is_system' => true],
            ['code' => '1501', 'name' => 'Motor Vehicles', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'parent_code' => '1500', 'is_postable' => true, 'is_system' => true],
            ['code' => '1502', 'name' => 'Furniture & Equipment', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'parent_code' => '1500', 'is_postable' => true, 'is_system' => true],
            ['code' => '1503', 'name' => 'Land & Buildings', 'account_type' => Account::TYPE_ASSET, 'normal_balance' => 'dr', 'parent_code' => '1500', 'is_postable' => true, 'is_system' => true],

            // ---- Liabilities (2xxx) ----
            ['code' => '2000', 'name' => 'Accounts Payable', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],
            ['code' => '2100', 'name' => 'Salaries Payable', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],
            ['code' => '2300', 'name' => 'Loans Payable', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'is_postable' => false, 'is_system' => true],
            ['code' => '2301', 'name' => 'Equity Bank Loan 8659', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],
            ['code' => '2302', 'name' => 'Equity Bank Loan 2564', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],
            ['code' => '2303', 'name' => 'Equity Bank Loan 986', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],
            ['code' => '2304', 'name' => 'Equity Bank Loan 7419', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],
            ['code' => '2305', 'name' => 'I&M Bank Loan', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],
            ['code' => '2306', 'name' => 'Family Bank Loan', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],
            ['code' => '2307', 'name' => 'Jackfruit Microfinance Loan', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],
            ['code' => '2308', 'name' => 'ED Partners Loan', 'account_type' => Account::TYPE_LIABILITY, 'normal_balance' => 'cr', 'parent_code' => '2300', 'is_postable' => true],

            // ---- Equity (3xxx) ----
            ['code' => '3000', 'name' => 'Retained Earnings', 'account_type' => Account::TYPE_EQUITY, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],

            // ---- Revenue (4xxx) ----
            ['code' => '4000', 'name' => 'School Fees Income', 'account_type' => Account::TYPE_REVENUE, 'normal_balance' => 'cr', 'is_postable' => true, 'is_system' => true],
            ['code' => '4100', 'name' => 'Co-curricular & Activity Income', 'account_type' => Account::TYPE_REVENUE, 'normal_balance' => 'cr', 'is_postable' => true],

            // ---- Expenses (5xxx) ----
            ['code' => '5000', 'name' => 'Operating Expenses', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'is_postable' => false, 'is_system' => true],

            // Transport & vehicles
            ['code' => '5100', 'name' => 'Transport & Vehicles', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => false, 'is_system' => true],
            ['code' => '5101', 'name' => 'Fuel', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true, 'is_system' => true],
            ['code' => '5102', 'name' => 'Vehicle Repairs & Maintenance', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true, 'is_system' => true],
            ['code' => '5103', 'name' => 'Vehicle Insurance', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true],
            ['code' => '5104', 'name' => 'Vehicle Licensing & Compliance', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true],
            ['code' => '5105', 'name' => 'Car Hire', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true],
            ['code' => '5106', 'name' => 'Vehicle Taxes', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5100', 'is_postable' => true],

            // Staff costs
            ['code' => '5200', 'name' => 'Salaries & Wages', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5210', 'name' => 'Staff Medical', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5230', 'name' => 'Statutory Remittances', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => false],
            ['code' => '5231', 'name' => 'PAYE', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5230', 'is_postable' => true],
            ['code' => '5232', 'name' => 'NSSF', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5230', 'is_postable' => true],
            ['code' => '5233', 'name' => 'NHIF / SHIF', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5230', 'is_postable' => true],
            ['code' => '5234', 'name' => 'Housing Levy', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5230', 'is_postable' => true],
            ['code' => '5235', 'name' => 'NITA', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5230', 'is_postable' => true],

            // Utilities
            ['code' => '5300', 'name' => 'Utilities', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => false, 'is_system' => true],
            ['code' => '5301', 'name' => 'Internet & Wi-Fi', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true, 'is_system' => true],
            ['code' => '5302', 'name' => 'Phone & Communication', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true, 'is_system' => true],
            ['code' => '5303', 'name' => 'Electricity', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true],
            ['code' => '5304', 'name' => 'Water', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true],
            ['code' => '5305', 'name' => 'Generator Running', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true],
            ['code' => '5306', 'name' => 'Garbage Collection', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true],
            ['code' => '5307', 'name' => 'Sanitary Services', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5300', 'is_postable' => true],

            // Administration & office
            ['code' => '5400', 'name' => 'Office & Administration', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5410', 'name' => 'Stationery', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5420', 'name' => 'Licenses & Permits', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5430', 'name' => 'Audit & Professional Fees', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5440', 'name' => 'Taxes & Levies', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5450', 'name' => 'Rent', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5460', 'name' => 'Donations', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],

            // Teaching & learning
            ['code' => '5500', 'name' => 'Food & Catering', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5510', 'name' => 'Textbooks', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5520', 'name' => 'Examinations', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
            ['code' => '5530', 'name' => 'Uniforms', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],

            // Construction & repairs
            ['code' => '5600', 'name' => 'Construction & Repairs', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5610', 'name' => 'Construction Labour', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],

            // Activities & other
            ['code' => '5700', 'name' => 'Bank & Transaction Charges', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true, 'is_system' => true],
            ['code' => '5750', 'name' => 'Co-curricular Activities', 'account_type' => Account::TYPE_EXPENSE, 'normal_balance' => 'dr', 'parent_code' => '5000', 'is_postable' => true],
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
            ['code' => 'TRANSPORT', 'name' => 'Transport & Vehicles', 'is_header' => true, 'children' => [
                ['code' => 'FUEL', 'name' => 'Fuel', 'account_code' => '5101'],
                ['code' => 'VEH-REPAIRS', 'name' => 'Vehicle Repairs & Wash', 'account_code' => '5102'],
                ['code' => 'VEH-SERVICE', 'name' => 'Vehicle Servicing & Maintenance', 'account_code' => '5102'],
                ['code' => 'VEH-INSURANCE', 'name' => 'Vehicle Insurance', 'account_code' => '5103'],
                ['code' => 'VEH-INSPECTION', 'name' => 'Vehicle Inspection', 'account_code' => '5104'],
                ['code' => 'VEH-LOGBOOK', 'name' => 'Vehicle Logbook Transfer', 'account_code' => '5104'],
                ['code' => 'NTSA', 'name' => 'NTSA Charges', 'account_code' => '5104'],
                ['code' => 'SPEED-GOVERNOR', 'name' => 'Speed Governor', 'account_code' => '5104'],
                ['code' => 'VEH-TRACKING', 'name' => 'Vehicle Tracking', 'account_code' => '5104'],
                ['code' => 'CAR-HIRE', 'name' => 'Car Hire', 'account_code' => '5105'],
                ['code' => 'VEH-ADVANCE-TAX', 'name' => 'Advance Tax on Vehicles', 'account_code' => '5106'],
                ['code' => 'VEH-VALUATION', 'name' => 'Vehicle Valuation', 'account_code' => '5430'],
                ['code' => 'VEH-PURCHASE', 'name' => 'Vehicle Purchase (Asset)', 'account_code' => '1501'],
            ]],
            ['code' => 'STAFF', 'name' => 'Staff Costs', 'is_header' => true, 'children' => [
                ['code' => 'SALARIES', 'name' => 'Salaries', 'account_code' => '5200'],
                ['code' => 'WAGES', 'name' => 'Wages (Casual Labour)', 'account_code' => '5200'],
                ['code' => 'MEDICAL', 'name' => 'Medical', 'account_code' => '5210'],
            ]],
            ['code' => 'STATUTORY', 'name' => 'Statutory Remittances', 'is_header' => true, 'children' => [
                ['code' => 'PAYE', 'name' => 'PAYE', 'account_code' => '5231'],
                ['code' => 'NSSF', 'name' => 'NSSF', 'account_code' => '5232'],
                ['code' => 'NHIF', 'name' => 'NHIF / SHIF', 'account_code' => '5233'],
                ['code' => 'HOUSING', 'name' => 'Housing Levy', 'account_code' => '5234'],
                ['code' => 'NITA', 'name' => 'NITA', 'account_code' => '5235'],
            ]],
            ['code' => 'LOANS', 'name' => 'Loan Repayments', 'is_header' => true, 'children' => [
                ['code' => 'LOAN-EQUITY-8659', 'name' => 'Loan - Equity 8659', 'account_code' => '2301'],
                ['code' => 'LOAN-EQUITY-2564', 'name' => 'Loan - Equity 2564', 'account_code' => '2302'],
                ['code' => 'LOAN-EQUITY-986', 'name' => 'Loan - Equity 986', 'account_code' => '2303'],
                ['code' => 'LOAN-EQUITY-7419', 'name' => 'Loan - Equity 7419', 'account_code' => '2304'],
                ['code' => 'LOAN-IM-BANK', 'name' => 'Loan - I&M Bank', 'account_code' => '2305'],
                ['code' => 'LOAN-FAMILY-BANK', 'name' => 'Loan - Family Bank', 'account_code' => '2306'],
                ['code' => 'LOAN-JACKFRUIT', 'name' => 'Loan - Jackfruit Microfinance', 'account_code' => '2307'],
                ['code' => 'LOAN-ED-PARTNERS', 'name' => 'Loan - ED Partners', 'account_code' => '2308'],
            ]],
            ['code' => 'UTILITIES', 'name' => 'Utilities', 'is_header' => true, 'children' => [
                ['code' => 'ELECTRICITY', 'name' => 'Electricity', 'account_code' => '5303'],
                ['code' => 'WATER', 'name' => 'Water', 'account_code' => '5304'],
                ['code' => 'GENERATOR', 'name' => 'Generator', 'account_code' => '5305'],
                ['code' => 'INTERNET', 'name' => 'Internet', 'account_code' => '5301'],
                ['code' => 'WIFI', 'name' => 'Wi-Fi', 'account_code' => '5301'],
                ['code' => 'COMMUNICATION', 'name' => 'Communication / Airtime', 'account_code' => '5302'],
                ['code' => 'TRASH', 'name' => 'Trash / Garbage', 'account_code' => '5306'],
                ['code' => 'SANITARY', 'name' => 'Sanitary Services', 'account_code' => '5307'],
            ]],
            ['code' => 'ADMIN', 'name' => 'Administration & Office', 'is_header' => true, 'children' => [
                ['code' => 'OFFICE', 'name' => 'Office Expenses', 'account_code' => '5400'],
                ['code' => 'STATIONERY', 'name' => 'Stationery', 'account_code' => '5410'],
                ['code' => 'LICENSE', 'name' => 'Licenses & Permits', 'account_code' => '5420'],
                ['code' => 'AUDIT-FEE', 'name' => 'Audit Fees', 'account_code' => '5430'],
                ['code' => 'LAND-VALUATION', 'name' => 'Land Valuation', 'account_code' => '5430'],
                ['code' => 'ADVANCE-TAX', 'name' => 'Advance Tax', 'account_code' => '5440'],
                ['code' => 'RENT', 'name' => 'Rent', 'account_code' => '5450'],
                ['code' => 'DONATION', 'name' => 'Donations', 'account_code' => '5460'],
                ['code' => 'FURNITURE', 'name' => 'Furniture (Asset)', 'account_code' => '1502'],
                ['code' => 'ASSETS', 'name' => 'Equipment & Assets', 'account_code' => '1502'],
            ]],
            ['code' => 'TEACHING', 'name' => 'Teaching & Learning', 'is_header' => true, 'children' => [
                ['code' => 'TEXTBOOKS', 'name' => 'Textbooks', 'account_code' => '5510'],
                ['code' => 'EXAM', 'name' => 'Examinations', 'account_code' => '5520'],
                ['code' => 'UNIFORM', 'name' => 'Uniforms', 'account_code' => '5530'],
            ]],
            ['code' => 'CATERING', 'name' => 'Catering', 'is_header' => true, 'children' => [
                ['code' => 'FOOD', 'name' => 'Food', 'account_code' => '5500'],
            ]],
            ['code' => 'ACTIVITIES', 'name' => 'Co-curricular Activities', 'is_header' => true, 'children' => [
                ['code' => 'ACT-BALLET', 'name' => 'Ballet', 'account_code' => '5750'],
                ['code' => 'ACT-SKATING', 'name' => 'Skating', 'account_code' => '5750'],
                ['code' => 'ACT-TAEKWONDO', 'name' => 'Taekwondo', 'account_code' => '5750'],
                ['code' => 'ACT-MUSIC', 'name' => 'Music', 'account_code' => '5750'],
                ['code' => 'ACT-FRENCH', 'name' => 'French', 'account_code' => '5750'],
            ]],
            ['code' => 'BUILDINGS', 'name' => 'Buildings & Construction', 'is_header' => true, 'children' => [
                ['code' => 'CONSTRUCTION', 'name' => 'Construction', 'account_code' => '5600'],
                ['code' => 'LABOUR-CONSTRUCTION', 'name' => 'Labour - Construction', 'account_code' => '5610'],
                ['code' => 'GENERAL-REPAIRS', 'name' => 'General Repairs', 'account_code' => '5600'],
            ]],
            ['code' => 'OTHER', 'name' => 'Other', 'is_header' => true, 'children' => [
                ['code' => 'TXN_COST', 'name' => 'Bank & Transaction Charges', 'account_code' => '5700'],
                ['code' => 'MISC', 'name' => 'Miscellaneous', 'account_code' => '5999'],
            ]],
        ];

        $order = 0;
        foreach ($tree as $node) {
            $this->upsertCategoryNode($node, null, $accounts, $order);
            $order += 10;
        }

        $this->deactivateLegacyCategories();
    }

    /**
     * @param  array<string, int>  $accounts
     */
    protected function upsertCategoryNode(array $node, ?int $parentId, array $accounts, int $sortOrder = 0): void
    {
        $accountId = isset($node['account_code']) ? ($accounts[$node['account_code']] ?? null) : null;

        $category = ExpenseCategory::updateOrCreate(
            ['code' => $node['code']],
            [
                'name' => $node['name'],
                'parent_id' => $parentId,
                'account_id' => $accountId,
                'is_header' => (bool) ($node['is_header'] ?? false),
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );

        $childOrder = 0;
        foreach ($node['children'] ?? [] as $child) {
            $this->upsertCategoryNode($child, $category->id, $accounts, $childOrder);
            $childOrder += 10;
        }
    }

    /**
     * Hide the generic placeholder categories the previous seeder created,
     * which are now superseded by the detailed school tree above.
     */
    protected function deactivateLegacyCategories(): void
    {
        $legacyCodes = [
            'FUEL-DIESEL',
            'FUEL-PETROL',
            'MOTOR_MAINT',
            'PHONE',
            'PAYROLL',
            'REPAIRS',
            'MAINTENANCE',
            'WITHDRAWAL',
        ];

        ExpenseCategory::whereIn('code', $legacyCodes)->update(['is_active' => false]);
    }
}
