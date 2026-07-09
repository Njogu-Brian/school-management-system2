<?php

namespace Database\Seeders;

use App\Models\DeductionType;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\SalaryStructure;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffCategory;
use App\Models\StaffStatutoryExemption;
use App\Models\StatutoryRuleset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Seeds HR payroll master data from the June 2026 budget + IM Bank / SHIF / NSSF / Staff Registration files.
 *
 * Safe to re-run: matches staff by id_number / kra_pin / bank_account / name, then updates.
 *
 * Usage (production):
 *   php artisan db:seed --class=HrPayrollJune2026Seeder --force
 */
class HrPayrollJune2026Seeder extends Seeder
{
    private const EMAIL_DOMAIN = 'royalkingsschools.sc.ke';

    public function run(): void
    {
        $this->seedLookups();
        $this->seedDeductionTypes();

        $staffRows = $this->staffMasterData();
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($staffRows, &$created, &$updated) {
            foreach ($staffRows as $row) {
                [$staff, $isNew] = $this->upsertStaff($row);
                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            $this->seedJunePayroll($staffRows);
        });

        $this->command?->info("HR payroll seed complete. Staff created: {$created}, updated: {$updated}.");
        $this->command?->warn('Review staff with placeholder emails / missing roles and update as needed.');
    }

    private function seedLookups(): void
    {
        $departments = [
            'Pre School',
            'Grade 1-3',
            'Grade 4-6',
            'Grade 7-9',
            'Support Staff',
            'Office',
            'Directors',
        ];
        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        $categories = ['Teaching', 'Support', 'Office', 'Director'];
        foreach ($categories as $name) {
            StaffCategory::firstOrCreate(['name' => $name]);
        }

        $titles = [
            'Teacher', 'Driver', 'Cleaner', 'Cook', 'Caretaker', 'Watchman',
            'Office Staff', 'Director', 'Support Staff',
        ];
        foreach ($titles as $name) {
            JobTitle::firstOrCreate(['name' => $name]);
        }
    }

    private function seedDeductionTypes(): void
    {
        $types = [
            ['code' => 'KIDS_FEES', 'name' => 'Kids School Fees', 'is_statutory' => false],
            ['code' => 'UNIFORM', 'name' => 'School Uniform', 'is_statutory' => false],
            ['code' => 'LOAN', 'name' => 'Staff Loan', 'is_statutory' => false],
            ['code' => 'ADVANCE', 'name' => 'Salary Advance', 'is_statutory' => false],
        ];
        foreach ($types as $t) {
            DeductionType::updateOrCreate(
                ['code' => $t['code']],
                [
                    'name' => $t['name'],
                    'calculation_method' => 'fixed_amount',
                    'default_amount' => 0,
                    'is_active' => true,
                    'is_statutory' => $t['is_statutory'],
                    'requires_approval' => false,
                ]
            );
        }
    }

    /**
     * Master staff + June payroll figures compiled from:
     * - BUDGET 2023-2026 JUNE 2026.pdf
     * - Salary Upload IMBank JUNE2026.xls
     * - SHIF MAY 2026.xlsx / NSSF MAY 2026.xlsx
     * - Staff Registration PDF
     *
     * @return array<int, array<string, mixed>>
     */
    private function staffMasterData(): array
    {
        // Columns: first, last, middle?, id_number, kra, nssf, nhif, phone, bank_name, bank_account,
        // payment_method, department, job_title, category, role, gross, kids_fees, uniform, advance, loan,
        // nssf_amt, shif_amt, paye_amt, housing_amt, net, exemptions[], personal_email, staff_no
        return [
            // PRE SCHOOL
            $this->row('Susan', 'Wanjiru', null, null, null, null, null, '+254700273226', null, null, 'mpesa', 'Pre School', 'Teacher', 'Teaching', 'Teacher', 13000, 2000, 0, 0, 0, 0, 357.50, 0, 0, 10642.50, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('Faith', 'Chelangat', null, '37447752', 'A018113889X', null, null, '+254796897166', null, null, 'mpesa', 'Pre School', 'Teacher', 'Teaching', 'Teacher', 15000, 0, 0, 0, 0, 0, 412.50, 0, 0, 14587.50, ['nssf', 'paye', 'housing_levy'], 'fchelangat538@gmail.com', null),
            $this->row('Judy', 'Kanana', null, '34266404', 'A010914635U', '2016750159', 'CR6756928782220-8', '+254792014422', 'EQUITY BANK - 68', '0400172719413', 'bank', 'Pre School', 'Teacher', 'Teaching', 'Teacher', 16000, 1000, 0, 960, 0, 440, 440, 0, 240, 13360, [], null, '27'),
            $this->row('Catherine', 'Ndungu', 'Watati', '20140426', 'A005280109C', '488126827', 'CR3555743436173-2', '+254799587593', 'EQUITY BANK - 68', '0120190499405', 'bank', 'Pre School', 'Teacher', 'Teaching', 'Teacher', 20000, 1200, 0, 0, 0, 0, 550, 0, 300, 17950, ['nssf', 'paye'], 'katehuho@gmail.com', '31'),
            $this->row('Tuzzy', 'Mathoko', null, '34070231', 'A014786919H', '2028203109', 'CR7362222811622', '+254716117141', 'EQUITY BANK - 68', '0890179607291', 'bank', 'Pre School', 'Teacher', 'Teaching', 'Teacher', 18000, 0, 0, 0, 0, 1080, 495, 0, 270, 16155, ['paye'], 'tuzzywm01@gmail.com', '41'),
            $this->row('Florence', 'Mwihaki', null, '38411106', 'A014131842X', '2056415579', null, '+254707481833', 'KENYA COMMERCIAL BANK - 01', '1333430620', 'bank', 'Pre School', 'Teacher', 'Teaching', 'Teacher', 20000, 0, 0, 0, 0, 0, 550, 0, 300, 19150, ['nssf', 'paye'], 'kariukimwihaki62@gmail.com', '51'),

            // GRADE 1-3
            $this->row('Elizabeth', 'Kimeu', null, '29517467', null, null, null, null, null, null, 'mpesa', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 18000, 1000, 0, 0, 0, 0, 495, 0, 0, 16505, ['nssf', 'paye', 'housing_levy'], null, null),
            $this->row('Mercy', 'Wambui', 'Njoki', '34184960', 'A014335041I', '2029624876', 'CR8944829170388-2', '+254712253187', 'KENYA COMMERCIAL BANK - 01', '1271924854', 'bank', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 30000, 0, 0, 0, 0, 1800, 825, 731.25, 450, 26193.75, [], 'wambuimercy917@gmail.com', '30'),
            $this->row('Lyn', 'Odhiambo', null, '38289195', 'A015908436H', '2032463919', 'CR1098606028039-6', '+254743126476', 'EQUITY BANK - 68', '1450180379354', 'bank', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 22340, 0, 0, 0, 0, 1340.4, 614.35, 0, 335.10, 20050.15, ['paye'], 'lynopole@gmail.com', '33'),
            $this->row('Ann', 'Jepchumba', null, '31739188', 'A011086468X', null, null, null, 'EQUITY BANK - 68', '0270171991412', 'bank', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 16000, 0, 0, 0, 0, 0, 440, 0, 240, 15320, ['nssf', 'paye'], null, '42'),
            $this->row('Esther', 'Muthoni', null, '24088993', 'A011750296Y', null, 'CR2268596648497-1', '+254711198411', 'EQUITY BANK - 68', '0880195515945', 'bank', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 19000, 2000, 0, 0, 0, 0, 522.50, 0, 285, 16192.50, ['nssf', 'paye'], null, '43'),
            $this->row('Lucy', 'Ayuko', null, '34730791', null, null, null, '+254741366465', 'FAMILY BANK - 70', '101000009600', 'bank', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 18000, 1000, 1000, 0, 0, 0, 495, 0, 270, 15235, ['nssf', 'paye'], 'lucyayuko23@gmail.com', '47'),

            // GRADE 4-6
            $this->row('Eunice', 'Kagendo', null, '38094994', 'A021388761I', null, 'CR5620093245104-4', '+254722644132', 'KENYA COMMERCIAL BANK - 01', '1333432011', 'bank', 'Grade 4-6', 'Teacher', 'Teaching', 'Teacher', 22000, 0, 0, 0, 0, 0, 605, 0, 330, 21065, ['nssf', 'paye'], 'eunicekagendo11@gmail.com', '32'),
            $this->row('Phylis', 'Wanjeri', null, '39906657', null, null, null, '+254703360235', 'KENYA COMMERCIAL BANK - 01', '1351666797', 'bank', 'Grade 4-6', 'Teacher', 'Teaching', 'Teacher', 18000, 0, 0, 0, 0, 0, 495, 0, 270, 17235, ['nssf', 'paye'], 'njengaphyllis208@gmail.com', '46'),
            $this->row('Peter', 'Michugu', null, '30721518', 'A010900578X', '201670888X', 'CR0240559369616-8', '+254705734120', 'KENYA COMMERCIAL BANK - 01', '1347785078', 'bank', 'Grade 4-6', 'Teacher', 'Teaching', 'Teacher', 18000, 0, 0, 0, 0, 1080, 495, 0, 270, 16155, ['paye'], 'pmichu4@gmail.com', '36'),
            $this->row('Silas', 'Kamau', null, null, null, null, null, null, 'FAMILY BANK - 70', '101000013830', 'bank', 'Grade 4-6', 'Teacher', 'Teaching', 'Teacher', 17000, 2000, 0, 0, 0, 0, 467.50, 0, 255, 14277.50, ['nssf', 'paye'], null, '52'),

            // GRADE 7-9
            $this->row('Peter', 'Kariuki', 'Mburu', '34443594', 'A013216942P', '2056674481', 'CR7670317194263-7', '+254716783246', 'FAMILY BANK - 70', '047000044030', 'bank', 'Grade 7-9', 'Teacher', 'Teaching', 'Teacher', 25000, 4000, 0, 1500, 0, 0, 687.50, 0, 375, 18437.50, ['nssf', 'paye'], 'Karlpeters802@gmail.com', '29'),
            $this->row('Cornelius', 'Kipchirchir', null, '34443416', 'A016972961J', null, null, '+254113593030', null, null, 'mpesa', 'Grade 7-9', 'Teacher', 'Teaching', 'Teacher', 18800, 0, 0, 0, 0, 0, 517, 0, 282, 18001, ['nssf', 'paye'], 'Kemboicorneliu24@gmail.com', null),
            $this->row('Ruth', 'Kemunto', 'Ogechi', '33728512', 'A009934773P', '203918283X', 'CR3936500459723', '+254799572557', 'COOPERATIVE BANK OF KENYA - 11', '01100113474001', 'bank', 'Grade 7-9', 'Teacher', 'Teaching', 'Teacher', 28300, 0, 0, 0, 0, 1698, 778.25, 349.81, 424.50, 25049.44, [], 'ruthogechi2024@gmail.com', '50'),
            $this->row('Brenda', 'Cheptoo', null, '39122278', 'A015451876V', null, null, '+254714473402', 'EQUITY BANK - 68', '0280180050880', 'bank', 'Grade 7-9', 'Teacher', 'Teaching', 'Teacher', 30000, 0, 0, 0, 0, 0, 825, 731.25, 450, 27993.75, ['nssf'], 'cheptoobrenda67@gmail.com', '49'),

            // SUPPORT
            $this->row('Lydia', 'Wanjiku', null, null, null, null, null, '+254703436324', null, null, 'mpesa', 'Support Staff', 'Cleaner', 'Support', 'Staff', 11000, 1000, 0, 0, 0, 0, 0, 0, 0, 10000, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('Magdalene', 'Njeri', null, null, null, null, null, '+254725906925', null, null, 'mpesa', 'Support Staff', 'Cleaner', 'Support', 'Staff', 10500, 0, 0, 0, 0, 0, 0, 0, 0, 10500, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('Tirus', 'Mwaura', null, null, null, null, null, '+254704271326', 'EQUITY BANK - 68', '1780184346553', 'bank', 'Support Staff', 'Driver', 'Support', 'Driver', 20000, 0, 0, 0, 0, 0, 550, 0, 300, 19150, ['nssf', 'paye'], null, '40'),
            $this->row('George', 'Njoroge', null, '20583822', 'A003018319I', '514848928', null, '+254703949275', 'EQUITY BANK - 68', '1780184346777', 'bank', 'Support Staff', 'Driver', 'Support', 'Driver', 19000, 0, 0, 0, 0, 1140, 522.50, 0, 285, 17052.50, ['paye'], null, '39'),
            $this->row('Gerald', 'Mbugua', null, null, null, null, null, null, 'EQUITY BANK - 68', '0040195205221', 'bank', 'Support Staff', 'Driver', 'Support', 'Driver', 18000, 0, 0, 0, 0, 0, 495, 0, 270, 17235, ['nssf', 'paye'], null, '45'),
            $this->row('Silas', 'Driver', null, null, null, null, null, null, null, null, 'mpesa', 'Support Staff', 'Driver', 'Support', 'Driver', 15000, 0, 0, 0, 0, 0, 0, 0, 0, 15000, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('Caroline', 'Kanana', null, '32007056', 'A014881794I', '2036977052', '19538600', '+254727686069', 'EQUITY BANK - 68', '1780174321098', 'bank', 'Support Staff', 'Cook', 'Support', 'Staff', 13500, 2000, 0, 810, 0, 0, 371.25, 0, 202.50, 10116.25, ['nssf', 'paye'], null, '25'),
            $this->row('Phylis', 'Mutimbi', null, null, null, null, null, '+254729779308', 'EQUITY BANK - 68', '1780184424710', 'bank', 'Support Staff', 'Cleaner', 'Support', 'Staff', 10000, 1000, 0, 0, 0, 0, 300, 0, 150, 8550, ['nssf', 'paye'], null, '26'),
            $this->row('Kevin', 'Caretaker', null, null, null, null, null, null, null, null, 'mpesa', 'Support Staff', 'Caretaker', 'Support', 'Staff', 10500, 0, 0, 0, 0, 0, 0, 0, 0, 10500, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('Sailas', 'Watchman', null, null, null, null, null, null, null, null, 'mpesa', 'Support Staff', 'Watchman', 'Support', 'Staff', 9500, 0, 0, 0, 0, 0, 0, 0, 0, 9500, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('Simon', 'Caretaker', null, null, null, null, null, null, null, null, 'mpesa', 'Support Staff', 'Caretaker', 'Support', 'Staff', 10000, 0, 0, 0, 0, 0, 0, 0, 0, 10000, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('NC', 'Staff', null, null, null, null, null, null, null, null, 'mpesa', 'Support Staff', 'Support Staff', 'Support', 'Staff', 15000, 0, 0, 0, 0, 0, 0, 0, 0, 15000, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),
            $this->row('Tonny', 'Staff', null, null, null, null, null, null, null, null, 'mpesa', 'Support Staff', 'Support Staff', 'Support', 'Staff', 12000, 0, 0, 0, 0, 0, 0, 0, 0, 12000, ['nssf', 'shif', 'paye', 'housing_levy'], null, null),

            // OFFICE
            $this->row('Sharon', 'James', null, '40103927', 'A018834139B', '2045688148', null, null, null, null, 'mpesa', 'Office', 'Office Staff', 'Office', 'Secretary', 20000, 0, 0, 0, 0, 1200, 550, 0, 300, 17950, ['paye'], null, null),
            $this->row('Lyn', 'Office', null, null, null, null, null, null, 'EQUITY BANK - 68', null, 'bank', 'Office', 'Office Staff', 'Office', 'Secretary', 30000, 0, 0, 0, 0, 1800, 825, 731.25, 450, 26193.75, [], null, null),

            // DIRECTORS
            $this->row('Brian', 'Njogu', 'Murage', '34165387', 'A010123476H', '2027410852', 'CR0591266695838-0', '+254708225397', 'I & M BANK LTD - 57', '03604789316150', 'bank', 'Directors', 'Director', 'Director', 'Director', 19000, 0, 0, 0, 0, 1140, 522.50, 0, 285, 17052.50, ['paye'], null, '35'),
            $this->row('Dickson', 'Njogu', 'Murage', '10316164', 'A002353152J', '049316915', 'CR9176379405828-7', '+254721404848', 'EQUITY BANK - 68', null, 'bank', 'Directors', 'Director', 'Director', 'Director', 35000, 0, 0, 0, 0, 2100, 962.50, 1853.13, 525, 29559.37, [], 'pstdickson@gmail.com', null),
            $this->row('Purity', 'Njogu', 'Mwari', '9854143', 'A002279824Y', '564869813', 'CR5887255501429-7', '+254722716989', 'EQUITY BANK - 68', null, 'bank', 'Directors', 'Director', 'Director', 'Director', 35000, 0, 0, 0, 0, 2100, 962.50, 1853.13, 525, 29559.37, [], null, null),

            // Extra from SHIF/NSSF not clearly on budget (ensure they exist)
            $this->row('Lilian', 'Atieno', 'Ojwang', '21593142', 'A005616100K', '2000445518', 'CRI200445518', '+254738115823', 'EQUITY BANK - 68', '0130185775126', 'bank', 'Grade 7-9', 'Teacher', 'Teaching', 'Teacher', 30000, 0, 0, 0, 0, 0, 825, 0, 0, 0, ['nssf', 'paye', 'housing_levy'], 'lynearlyyearseducation@gmail.com', '48'),
            $this->row('Julia', 'Wanjiru', 'Peter', '30040097', 'A010660502B', '2017228333', null, null, null, null, 'mpesa', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 18000, 0, 0, 0, 0, 0, 495, 0, 0, 0, ['nssf', 'paye', 'housing_levy'], null, null),
            $this->row('Emmaculate', 'Sikuku', null, '39308109', null, null, null, '+254758813855', null, null, 'mpesa', 'Grade 1-3', 'Teacher', 'Teaching', 'Teacher', 0, 0, 0, 0, 0, 0, 517, 0, 0, 0, ['nssf', 'paye', 'housing_levy'], 'sikuku5emma@gmail.com', null),
        ];
    }

    private function row(
        string $first,
        string $last,
        ?string $middle,
        ?string $idNumber,
        ?string $kra,
        ?string $nssf,
        ?string $nhif,
        ?string $phone,
        ?string $bankName,
        ?string $bankAccount,
        string $paymentMethod,
        string $department,
        string $jobTitle,
        string $category,
        string $role,
        float $gross,
        float $kidsFees,
        float $uniform,
        float $advance,
        float $loan,
        float $nssfAmt,
        float $shifAmt,
        float $payeAmt,
        float $housingAmt,
        float $net,
        array $exemptions,
        ?string $personalEmail,
        ?string $staffNo,
    ): array {
        return compact(
            'first', 'last', 'middle', 'idNumber', 'kra', 'nssf', 'nhif', 'phone',
            'bankName', 'bankAccount', 'paymentMethod', 'department', 'jobTitle', 'category', 'role',
            'gross', 'kidsFees', 'uniform', 'advance', 'loan',
            'nssfAmt', 'shifAmt', 'payeAmt', 'housingAmt', 'net',
            'exemptions', 'personalEmail', 'staffNo'
        );
    }

    /**
     * @return array{0: Staff, 1: bool} [staff, wasCreated]
     */
    private function upsertStaff(array $row): array
    {
        $staff = $this->findExistingStaff($row);
        $isNew = false;

        $departmentId = Department::where('name', $row['department'])->value('id');
        $jobTitleId = JobTitle::where('name', $row['jobTitle'])->value('id');
        $categoryId = StaffCategory::where('name', $row['category'])->value('id');

        $workEmail = $staff?->work_email ?: $this->makeWorkEmail($row['first'], $row['last']);
        $phone = $row['phone'] ?: ($staff?->phone_number ?: '0700000000');

        if (! $staff) {
            $isNew = true;
            $user = User::firstOrCreate(
                ['email' => $workEmail],
                [
                    'name' => trim($row['first'] . ' ' . $row['last']),
                    'password' => Hash::make($row['idNumber'] ?: 'ChangeMe123!'),
                    'must_change_password' => true,
                ]
            );

            $roleName = $row['role'];
            if (Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$roleName]);
            } elseif (Role::where('name', 'Staff')->where('guard_name', 'web')->exists()) {
                $user->syncRoles(['Staff']);
            }

            $prefix = Setting::get('staff_id_prefix', 'STAFF');
            $start = Setting::getInt('staff_id_start', 1001);
            $staffId = $row['staffNo'] ? (string) $row['staffNo'] : ($prefix . $start);
            if (! $row['staffNo']) {
                Setting::setInt('staff_id_start', $start + 1);
            }

            $staff = Staff::create([
                'user_id' => $user->id,
                'staff_id' => $staffId,
                'first_name' => $row['first'],
                'middle_name' => $row['middle'],
                'last_name' => $row['last'],
                'work_email' => $workEmail,
                'personal_email' => $row['personalEmail'],
                'phone_number' => $phone,
                'id_number' => $row['idNumber'] ?: ('TEMP-' . Str::upper(Str::random(8))),
                'kra_pin' => $row['kra'],
                'nssf' => $row['nssf'],
                'nhif' => $row['nhif'],
                'bank_name' => $row['bankName'],
                'bank_account' => $row['bankAccount'],
                'payment_method' => $row['paymentMethod'],
                'department_id' => $departmentId,
                'job_title_id' => $jobTitleId,
                'staff_category_id' => $categoryId,
                'basic_salary' => $row['gross'] > 0 ? $row['gross'] : null,
                'status' => 'active',
                'employment_status' => 'active',
            ]);
        } else {
            $staff->fill(array_filter([
                'middle_name' => $row['middle'] ?: $staff->middle_name,
                'personal_email' => $row['personalEmail'] ?: $staff->personal_email,
                'phone_number' => $row['phone'] ?: $staff->phone_number,
                'id_number' => $row['idNumber'] ?: $staff->id_number,
                'kra_pin' => $row['kra'] ?: $staff->kra_pin,
                'nssf' => $row['nssf'] ?: $staff->nssf,
                'nhif' => $row['nhif'] ?: $staff->nhif,
                'bank_name' => $row['bankName'] ?: $staff->bank_name,
                'bank_account' => $row['bankAccount'] ?: $staff->bank_account,
                'payment_method' => $row['paymentMethod'] ?: ($staff->payment_method ?: 'bank'),
                'department_id' => $departmentId ?: $staff->department_id,
                'job_title_id' => $jobTitleId ?: $staff->job_title_id,
                'staff_category_id' => $categoryId ?: $staff->staff_category_id,
                'basic_salary' => $row['gross'] > 0 ? $row['gross'] : $staff->basic_salary,
                'status' => 'active',
            ], fn ($v) => $v !== null && $v !== ''));
            if ($row['staffNo'] && empty($staff->staff_id)) {
                $staff->staff_id = (string) $row['staffNo'];
            }
            $staff->save();
        }

        // Auto-correct payment method if bank account missing
        if (empty($staff->bank_account) && $staff->payment_method !== 'mpesa') {
            $staff->payment_method = 'mpesa';
            $staff->saveQuietly();
        }

        if ($row['gross'] > 0) {
            SalaryStructure::updateOrCreate(
                ['staff_id' => $staff->id, 'is_active' => true],
                [
                    'basic_salary' => $row['gross'],
                    'housing_allowance' => 0,
                    'transport_allowance' => 0,
                    'medical_allowance' => 0,
                    'other_allowances' => 0,
                    'effective_from' => '2026-06-01',
                    'is_active' => true,
                ]
            )->calculateTotals()->save();
        }

        // Statutory exemptions from budget (zero statutory amounts => exempt)
        StaffStatutoryExemption::where('staff_id', $staff->id)->delete();
        foreach ($row['exemptions'] as $code) {
            StaffStatutoryExemption::firstOrCreate([
                'staff_id' => $staff->id,
                'deduction_code' => strtolower($code),
            ]);
        }

        return [$staff, $isNew];
    }

    private function findExistingStaff(array $row): ?Staff
    {
        if (! empty($row['idNumber'])) {
            $s = Staff::where('id_number', $row['idNumber'])->first();
            if ($s) {
                return $s;
            }
        }
        if (! empty($row['kra'])) {
            $s = Staff::whereRaw('UPPER(kra_pin) = ?', [strtoupper($row['kra'])])->first();
            if ($s) {
                return $s;
            }
        }
        if (! empty($row['bankAccount'])) {
            $s = Staff::where('bank_account', $row['bankAccount'])->first();
            if ($s) {
                return $s;
            }
        }
        if (! empty($row['staffNo'])) {
            $s = Staff::where('staff_id', (string) $row['staffNo'])->first();
            if ($s) {
                return $s;
            }
        }

        return Staff::whereRaw('LOWER(first_name) = ? AND LOWER(last_name) = ?', [
            strtolower($row['first']),
            strtolower($row['last']),
        ])->first();
    }

    private function makeWorkEmail(string $first, string $last): string
    {
        $local = strtolower(substr(preg_replace('/[^a-zA-Z]/', '', $first) ?: 's', 0, 1)
            . '.' . preg_replace('/[^a-zA-Z]/', '', $last));
        $email = $local . '@' . self::EMAIL_DOMAIN;
        $i = 1;
        while (User::where('email', $email)->exists() || Staff::where('work_email', $email)->exists()) {
            $email = $local . $i . '@' . self::EMAIL_DOMAIN;
            $i++;
        }
        return $email;
    }

    private function seedJunePayroll(array $staffRows): void
    {
        $rulesetId = StatutoryRuleset::default()->value('id');

        $period = PayrollPeriod::updateOrCreate(
            ['year' => 2026, 'month' => 6],
            [
                'period_name' => 'June 2026',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
                'pay_date' => '2026-07-09',
                'statutory_ruleset_id' => $rulesetId,
                'status' => 'completed',
                'processed_at' => now(),
                'processed_by' => User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Admin']))->value('id'),
            ]
        );

        foreach ($staffRows as $row) {
            if (($row['gross'] ?? 0) <= 0) {
                continue;
            }
            $staff = $this->findExistingStaff($row);
            if (! $staff) {
                continue;
            }

            $record = PayrollRecord::firstOrNew([
                'payroll_period_id' => $period->id,
                'staff_id' => $staff->id,
            ]);

            $record->basic_salary = (float) $row['gross'];
            $record->housing_allowance = 0;
            $record->transport_allowance = 0;
            $record->medical_allowance = 0;
            $record->other_allowances = 0;
            $record->bonus = 0;
            $record->calculateTotals();

            $record->nssf_deduction = (float) $row['nssfAmt'];
            $record->shif_deduction = (float) $row['shifAmt'];
            $record->nhif_deduction = 0;
            $record->paye_deduction = (float) $row['payeAmt'];
            $record->housing_levy_deduction = (float) $row['housingAmt'];
            $record->advance_deduction = (float) $row['advance'];
            $record->deductions_breakdown = array_filter([
                'kids_fees' => (float) $row['kidsFees'],
                'uniform' => (float) $row['uniform'],
                'loan' => (float) $row['loan'],
            ], fn ($v) => $v > 0);
            $record->custom_deductions_total = (float) $row['kidsFees'] + (float) $row['uniform'] + (float) $row['loan'];
            $record->calculateTotals();

            // Prefer budget net if provided (avoids float drift)
            if (($row['net'] ?? 0) > 0) {
                $record->net_salary = (float) $row['net'];
                $record->total_deductions = round((float) $record->gross_salary - (float) $record->net_salary, 2);
            }

            $record->status = 'approved';
            $record->created_by = $period->processed_by;
            $record->save();
        }

        $period->refresh()->load('payrollRecords');
        $period->calculateTotals();
        $period->save();

        try {
            app(\App\Services\Finance\PayrollPostingService::class)->postAccrual(
                $period->fresh(),
                User::find($period->processed_by)
            );
        } catch (\Throwable $e) {
            $this->command?->warn('Payroll GL/expense posting skipped: ' . $e->getMessage());
        }
    }
}
