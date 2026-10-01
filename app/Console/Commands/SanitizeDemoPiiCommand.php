<?php

namespace App\Console\Commands;

use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\ParentCredentialsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Anonymize a cloned production database for the EduLynk Demo Academy showcase.
 * Run only against a dedicated demo DB (e.g. school_management_demo). Never on live.
 */
class SanitizeDemoPiiCommand extends Command
{
    protected $signature = 'demo:sanitize-pii
                            {--dry-run : Report actions without writing}
                            {--password=Demo@12345 : Shared password for staff/admin demo logins}
                            {--admin-email=admin@demo.school : Super Admin email}
                            {--force : Skip confirmation}';

    protected $description = 'Sanitize PII in the current database for EduLynk Demo Academy showcase use';

    private const SCHOOL_NAME = 'EduLynk Demo Academy';

    private const SCHOOL_EMAIL = 'demo@edulynk.co.ke';

    private const SCHOOL_PHONE = '+254700000000';

    private const SCHOOL_ADDRESS = 'Nairobi, Kenya';

    private const SCHOOL_MOTTO = 'Learning that connects';

    /** @var list<string> */
    private array $firstNames = [
        'Amani', 'Baraka', 'Chebet', 'Dalila', 'Eshe', 'Faraji', 'Grace', 'Halima', 'Imani', 'Jabari',
        'Kamau', 'Lina', 'Makena', 'Nia', 'Otieno', 'Purity', 'Quincy', 'Rehema', 'Sifa', 'Taji',
        'Uwase', 'Victor', 'Wanjiku', 'Xavier', 'Yara', 'Zuri', 'Brian', 'Mercy', 'Kelvin', 'Faith',
        'Daniel', 'Naomi', 'Samuel', 'Aisha', 'Peter', 'Joy', 'Collins', 'Ann', 'Eric', 'Lucy',
        'Abigail', 'Benson', 'Caroline', 'Dennis', 'Esther', 'Francis', 'Gladys', 'Henry', 'Irene', 'James',
        'Karen', 'Leonard', 'Millicent', 'Nicholas', 'Olive', 'Patrick', 'Rose', 'Stephen', 'Tabitha', 'Ursula',
        'Vincent', 'Wendy', 'Yvonne', 'Zachary', 'Beatrice', 'Caleb', 'Diana', 'Elijah', 'Fiona', 'George',
    ];

    /** @var list<string> */
    private array $middleNames = [
        'Kariuki', 'Wambui', 'Odhiambo', 'Njeri', 'Omondi', 'Achieng', 'Mwangi', 'Chepkemoi', 'Mutiso', 'Atieno',
        'Kimani', 'Wekesa', 'Nyambura', 'Ochieng', 'Chebet', 'Mutua', 'Aoko', 'Kiptoo', 'Wairimu', 'Otieno',
    ];

    /** @var list<string> */
    private array $lastNames = [
        'Otieno', 'Wanjiru', 'Kamau', 'Ochieng', 'Njeri', 'Mwangi', 'Achieng', 'Kiptoo', 'Mutua', 'Nyambura',
        'Okello', 'Cheruiyot', 'Waweru', 'Adhiambo', 'Karanja', 'Muthoni', 'Onyango', 'Chebet', 'Njoroge', 'Kimani',
        'Wekesa', 'Omollo', 'Koech', 'Githinji', 'Mboya', 'Rotich', 'Ndungu', 'Wafula', 'Kiplagat', 'Obiero',
        'Maina', 'Were', 'Sang', 'Gichuru', 'Owino', 'Kibet', 'Muriuki', 'Simiyu', 'Korir', 'Odongo',
    ];

    public function handle(ParentCredentialsService $parentCredentials): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $password = (string) $this->option('password');
        $adminEmail = strtolower(trim((string) $this->option('admin-email')));

        $dbName = (string) DB::connection()->getDatabaseName();
        $this->warn("Target database: {$dbName}");

        if (str_contains(strtolower($dbName), 'demo') === false && ! $this->option('force')) {
            $this->error('Refusing to run: database name does not contain "demo". Use --force only on a confirmed clone.');

            return self::FAILURE;
        }

        if (! $dryRun && ! $this->option('force') && ! $this->confirm("Sanitize PII in [{$dbName}]? This cannot be undone.", false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('Dry run — no writes.');
            $this->line('Would sanitize students, parents, families, users, staff, finance text, tokens, branding, logs.');

            return self::SUCCESS;
        }

        DB::disableQueryLog();

        $this->info('1/9 Students…');
        $this->sanitizeStudents();

        $this->info('2/9 Parents & families…');
        $this->sanitizeParentsAndFamilies();

        $this->info('3/9 Staff…');
        $this->sanitizeStaff();

        $this->info('4/9 Users & passwords…');
        $this->sanitizeUsers($password, $adminEmail);

        $this->info('5/9 Finance denormalized text…');
        $this->sanitizeFinanceText();

        $this->info('6/9 Tokens & sessions…');
        $this->wipeSecrets();

        $this->info('7/9 Branding…');
        $this->applyDemoBranding();

        $this->info('8/9 Logs…');
        $this->redactLogs();

        $this->info('9/9 Parent credentials + Super Admin…');
        $this->ensureSuperAdmin($adminEmail, $password);
        $result = $parentCredentials->resetAllParentTempPasswords(false);
        $this->line("Parent passwords reset: ok={$result['ok']} skipped={$result['skipped']} fail={$result['fail']}");

        $this->newLine();
        $this->info('Sanitization complete for '.self::SCHOOL_NAME);
        $this->line("Admin login: {$adminEmail} / {$password}");

        return self::SUCCESS;
    }

    private function sanitizeStudents(): void
    {
        $hasNameCol = Schema::hasColumn('students', 'name');
        $usedFullNames = [];
        $nameIndex = 0;

        DB::table('students')->orderBy('id')->chunkById(100, function ($students) use ($hasNameCol, &$usedFullNames, &$nameIndex) {
            foreach ($students as $student) {
                [$fn, $mn, $ln] = $this->nextUniqueNameParts($usedFullNames, $nameIndex);
                $admission = 'DEMO'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT);

                $payload = [
                    'admission_number' => $admission,
                    'first_name' => $fn,
                    'middle_name' => $mn,
                    'last_name' => $ln,
                    'nemis_number' => $student->nemis_number ? 'NEMIS'.$student->id : null,
                    'knec_assessment_number' => $student->knec_assessment_number ? 'KNEC'.$student->id : null,
                    'birth_certificate_entry_no' => $student->birth_certificate_entry_no ? 'BC'.$student->id : null,
                    'emergency_contact_name' => $fn.' '.$ln.' Guardian',
                    'emergency_contact_phone' => $this->fakePhone((int) $student->id + 1000),
                    'photo_path' => null,
                    'birth_certificate_path' => null,
                    'previous_schools' => null,
                    'transfer_to_school' => null,
                    'residential_area' => 'Nairobi Demo Estate',
                ];
                if ($hasNameCol) {
                    $payload['name'] = trim("{$fn} {$mn} {$ln}");
                }

                DB::table('students')->where('id', $student->id)->update($payload);
            }
        });

        // Keep denormalized legacy rows in sync
        if (Schema::hasTable('legacy_statement_terms')) {
            DB::statement('
                UPDATE legacy_statement_terms lst
                INNER JOIN students s ON s.id = lst.student_id
                SET lst.admission_number = s.admission_number,
                    lst.student_name = TRIM(CONCAT(s.first_name, " ", COALESCE(s.middle_name, ""), " ", s.last_name))
                WHERE lst.student_id IS NOT NULL
            ');
            DB::table('legacy_statement_terms')
                ->whereNull('student_id')
                ->update([
                    'admission_number' => DB::raw("CONCAT('DEMO', LPAD(id, 3, '0'))"),
                    'student_name' => 'Demo Student',
                ]);
        }
    }

    private function sanitizeParentsAndFamilies(): void
    {
        ParentInfo::query()->orderBy('id')->chunkById(100, function ($parents) {
            foreach ($parents as $parent) {
                $id = (int) $parent->id;
                $ff = $this->pick($this->firstNames, $id + 3);
                $fl = $this->pick($this->lastNames, $id + 5);
                $mf = $this->pick($this->firstNames, $id + 11);
                $ml = $this->pick($this->lastNames, $id + 17);
                $gf = $this->pick($this->firstNames, $id + 23);
                $gl = $this->pick($this->lastNames, $id + 29);

                DB::table('parent_info')->where('id', $id)->update([
                    'father_first_name' => $ff,
                    'father_middle_name' => null,
                    'father_last_name' => $fl,
                    'father_name' => "{$ff} {$fl}",
                    'father_phone' => $this->fakePhone($id * 10 + 1),
                    'father_whatsapp' => $this->fakePhone($id * 10 + 1),
                    'father_email' => "father{$id}@demo.school",
                    'father_id_number' => (string) (30000000 + $id),
                    'father_id_document' => null,
                    'father_employer' => 'Demo Employer Ltd',
                    'father_work_address' => 'Nairobi CBD',
                    'mother_first_name' => $mf,
                    'mother_middle_name' => null,
                    'mother_last_name' => $ml,
                    'mother_name' => "{$mf} {$ml}",
                    'mother_phone' => $this->fakePhone($id * 10 + 2),
                    'mother_whatsapp' => $this->fakePhone($id * 10 + 2),
                    'mother_email' => "mother{$id}@demo.school",
                    'mother_id_number' => (string) (40000000 + $id),
                    'mother_id_document' => null,
                    'mother_employer' => 'Demo Services',
                    'mother_work_address' => 'Westlands',
                    'guardian_first_name' => $gf,
                    'guardian_middle_name' => null,
                    'guardian_last_name' => $gl,
                    'guardian_name' => "{$gf} {$gl}",
                    'guardian_phone' => $this->fakePhone($id * 10 + 3),
                    'guardian_whatsapp' => $this->fakePhone($id * 10 + 3),
                    'guardian_email' => "guardian{$id}@demo.school",
                    'guardian_id_number' => (string) (50000000 + $id),
                    'guardian_employer' => null,
                    'guardian_work_address' => null,
                    'primary_contact_person' => "{$ff} {$fl}",
                ]);
            }
        });

        if (Schema::hasTable('families')) {
            DB::table('families')->orderBy('id')->chunkById(100, function ($families) {
                foreach ($families as $family) {
                    $id = (int) $family->id;
                    $fn = $this->pick($this->firstNames, $id + 41);
                    $ln = $this->pick($this->lastNames, $id + 43);
                    DB::table('families')->where('id', $id)->update([
                        'guardian_name' => "{$fn} {$ln}",
                        'father_name' => "{$fn} {$ln}",
                        'mother_name' => $this->pick($this->firstNames, $id + 47).' '.$ln,
                        'phone' => $this->fakePhone($id * 10 + 4),
                        'father_phone' => $this->fakePhone($id * 10 + 5),
                        'mother_phone' => $this->fakePhone($id * 10 + 6),
                        'email' => "family{$id}@demo.school",
                        'father_email' => "family.father{$id}@demo.school",
                        'mother_email' => "family.mother{$id}@demo.school",
                    ]);
                }
            });
        }

        $this->sanitizeOnlineAdmissions();
    }

    private function sanitizeOnlineAdmissions(): void
    {
        if (! Schema::hasTable('online_admissions')) {
            return;
        }

        $cols = Schema::getColumnListing('online_admissions');
        DB::table('online_admissions')->orderBy('id')->chunkById(50, function ($rows) use ($cols) {
            foreach ($rows as $row) {
                $id = (int) $row->id;
                $payload = [];
                foreach ([
                    'child_first_name', 'first_name', 'student_first_name',
                ] as $c) {
                    if (in_array($c, $cols, true)) {
                        $payload[$c] = $this->pick($this->firstNames, $id);
                    }
                }
                foreach ([
                    'child_last_name', 'last_name', 'student_last_name',
                ] as $c) {
                    if (in_array($c, $cols, true)) {
                        $payload[$c] = $this->pick($this->lastNames, $id + 2);
                    }
                }
                foreach (['parent_name', 'guardian_name', 'father_name', 'mother_name'] as $c) {
                    if (in_array($c, $cols, true)) {
                        $payload[$c] = $this->pick($this->firstNames, $id + 4).' '.$this->pick($this->lastNames, $id + 6);
                    }
                }
                foreach (['phone', 'phone_number', 'parent_phone', 'father_phone', 'mother_phone'] as $c) {
                    if (in_array($c, $cols, true)) {
                        $payload[$c] = $this->fakePhone($id + 9000);
                    }
                }
                foreach (['email', 'parent_email'] as $c) {
                    if (in_array($c, $cols, true)) {
                        $payload[$c] = "admission{$id}@demo.school";
                    }
                }
                foreach (['id_number', 'parent_id_number', 'nemis_number'] as $c) {
                    if (in_array($c, $cols, true)) {
                        $payload[$c] = (string) (60000000 + $id);
                    }
                }
                if ($payload !== []) {
                    DB::table('online_admissions')->where('id', $id)->update($payload);
                }
            }
        });

        if (Schema::hasTable('admission_applications')) {
            $appCols = Schema::getColumnListing('admission_applications');
            DB::table('admission_applications')->orderBy('id')->chunkById(50, function ($rows) use ($appCols) {
                foreach ($rows as $row) {
                    $id = (int) $row->id;
                    $payload = [];
                    if (in_array('parent_name', $appCols, true)) {
                        $payload['parent_name'] = $this->pick($this->firstNames, $id).' '.$this->pick($this->lastNames, $id);
                    }
                    if (in_array('child_name', $appCols, true)) {
                        $payload['child_name'] = $this->pick($this->firstNames, $id + 1).' '.$this->pick($this->lastNames, $id + 1);
                    }
                    if (in_array('phone', $appCols, true)) {
                        $payload['phone'] = $this->fakePhone($id + 8000);
                    }
                    if (in_array('email', $appCols, true)) {
                        $payload['email'] = "app{$id}@demo.school";
                    }
                    if (in_array('draft_token', $appCols, true)) {
                        $payload['draft_token'] = (string) Str::uuid();
                    }
                    if ($payload !== []) {
                        DB::table('admission_applications')->where('id', $id)->update($payload);
                    }
                }
            });
        }
    }

    private function sanitizeStaff(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        Staff::query()->orderBy('id')->chunkById(50, function ($staffRows) {
            foreach ($staffRows as $staff) {
                $id = (int) $staff->id;
                $fn = $this->pick($this->firstNames, $id + 101);
                $mn = $this->pick($this->middleNames, $id + 103);
                $ln = $this->pick($this->lastNames, $id + 107);
                $email = 'staff'.$id.'@demo.school';

                DB::table('staff')->where('id', $id)->update([
                    'first_name' => $fn,
                    'middle_name' => $mn,
                    'last_name' => $ln,
                    'work_email' => $email,
                    'personal_email' => 'personal.staff'.$id.'@demo.school',
                    'phone_number' => $this->fakePhone($id + 2000),
                    'id_number' => (string) (20000000 + $id),
                    'residential_address' => 'Demo Staff Quarters, Nairobi',
                    'emergency_contact_name' => $fn.' Next Of Kin',
                    'emergency_contact_phone' => $this->fakePhone($id + 3000),
                    'kra_pin' => 'A'.str_pad((string) $id, 9, '0', STR_PAD_LEFT).'Z',
                    'nssf' => (string) (70000000 + $id),
                    'nhif' => (string) (80000000 + $id),
                    'bank_name' => 'Demo Bank',
                    'bank_branch' => 'Nairobi',
                    'bank_account' => (string) (1000000000 + $id),
                    'photo' => null,
                    'biometric_emp_code' => $staff->biometric_emp_code ? 'BIO'.$id : null,
                ]);

                if ($staff->user_id) {
                    DB::table('users')->where('id', $staff->user_id)->update([
                        'name' => trim("{$fn} {$mn} {$ln}"),
                        'email' => $email,
                        'phone_number' => $this->fakePhone($id + 2000),
                        'google_id' => null,
                        'google_email' => null,
                    ]);
                }
            }
        });

        if (Schema::hasTable('vehicles')) {
            DB::table('vehicles')->update([
                'driver_name' => DB::raw("CONCAT('Driver ', id)"),
                'insurance_document' => null,
                'logbook_document' => null,
                'photo' => null,
                'chassis_number' => DB::raw("CONCAT('CHS', LPAD(id, 6, '0'))"),
            ]);
        }
    }

    private function sanitizeUsers(string $password, string $adminEmail): void
    {
        $hash = Hash::make($password);

        User::query()->orderBy('id')->chunkById(100, function ($users) use ($hash, $adminEmail) {
            foreach ($users as $user) {
                $id = (int) $user->id;
                // Staff-linked users already updated in sanitizeStaff; still reset password/tokens
                $isStaffLinked = Schema::hasTable('staff')
                    && DB::table('staff')->where('user_id', $id)->exists();

                $payload = [
                    'password' => $hash,
                    'remember_token' => null,
                    'google_id' => null,
                    'google_email' => null,
                    'unlock_pin_hash' => null,
                    'must_change_password' => false,
                ];

                if (! $isStaffLinked) {
                    if ($user->parent_id) {
                        $payload['name'] = 'Parent '.$user->parent_id;
                        $payload['email'] = 'parent'.$user->parent_id.'.u'.$id.'@demo.school';
                        $payload['phone_number'] = $this->fakePhone($id + 4000);
                    } else {
                        // Keep admin email reserved for ensureSuperAdmin
                        $payload['name'] = 'User '.$id;
                        $payload['email'] = $id === 1 ? $adminEmail : 'user'.$id.'@demo.school';
                        $payload['phone_number'] = $this->fakePhone($id + 5000);
                    }
                }

                DB::table('users')->where('id', $id)->update($payload);
            }
        });
    }

    private function sanitizeFinanceText(): void
    {
        if (Schema::hasTable('payments')) {
            DB::statement("
                UPDATE payments p
                LEFT JOIN students s ON s.id = p.student_id
                SET
                    p.payer_name = TRIM(CONCAT(COALESCE(s.first_name, 'Demo'), ' ', COALESCE(s.last_name, 'Payer'))),
                    p.transaction_code = CONCAT('TXN', LPAD(p.id, 8, '0')),
                    p.mpesa_receipt_number = IF(p.mpesa_receipt_number IS NULL OR p.mpesa_receipt_number = '', NULL, CONCAT('MP', LPAD(p.id, 8, '0'))),
                    p.mpesa_phone_number = IF(p.mpesa_phone_number IS NULL OR p.mpesa_phone_number = '', NULL, CONCAT('2547', LPAD(MOD(p.id, 100000000), 8, '0'))),
                    p.narration = 'Demo payment',
                    p.public_token = LEFT(MD5(CONCAT('pay', p.id)), 10),
                    p.hashed_id = LEFT(MD5(CONCAT('hid', p.id)), 10)
            ");
        }

        if (Schema::hasTable('bank_statement_transactions')) {
            DB::statement("
                UPDATE bank_statement_transactions b
                LEFT JOIN students s ON s.id = b.student_id
                SET
                    b.payer_name = TRIM(CONCAT(COALESCE(s.first_name, 'Demo'), ' ', COALESCE(s.last_name, 'Payer'))),
                    b.phone_number = IF(b.phone_number IS NULL OR b.phone_number = '', NULL, CONCAT('2547', LPAD(MOD(b.id, 100000000), 8, '0'))),
                    b.matched_admission_number = s.admission_number,
                    b.matched_student_name = TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))),
                    b.matched_phone_number = IF(b.matched_phone_number IS NULL OR b.matched_phone_number = '', NULL, CONCAT('2547', LPAD(MOD(b.id, 100000000), 8, '0'))),
                    b.description = 'Demo bank credit',
                    b.reference_number = CONCAT('REF', LPAD(b.id, 8, '0')),
                    b.raw_data = NULL,
                    b.statement_file_path = NULL
            ");
        }

        if (Schema::hasTable('mpesa_c2b_transactions')) {
            DB::statement("
                UPDATE mpesa_c2b_transactions m
                LEFT JOIN students s ON s.id = m.student_id
                SET
                    m.trans_id = CONCAT('C2B', LPAD(m.id, 8, '0')),
                    m.msisdn = CONCAT('2547', LPAD(MOD(m.id, 100000000), 8, '0')),
                    m.first_name = COALESCE(s.first_name, 'Demo'),
                    m.middle_name = COALESCE(s.middle_name, 'C'),
                    m.last_name = COALESCE(s.last_name, 'Payer'),
                    m.bill_ref_number = COALESCE(s.admission_number, CONCAT('DEMO', m.id)),
                    m.raw_data = NULL
            ");
        }

        if (Schema::hasTable('payment_transactions')) {
            $cols = Schema::getColumnListing('payment_transactions');
            $sets = [];
            if (in_array('phone_number', $cols, true)) {
                $sets[] = "phone_number = CONCAT('2547', LPAD(MOD(id, 100000000), 8, '0'))";
            }
            if (in_array('mpesa_receipt', $cols, true)) {
                $sets[] = "mpesa_receipt = CONCAT('PTR', LPAD(id, 8, '0'))";
            }
            if (in_array('transaction_id', $cols, true)) {
                $sets[] = "transaction_id = CONCAT('PTX', LPAD(id, 8, '0'))";
            }
            if ($sets !== []) {
                DB::statement('UPDATE payment_transactions SET '.implode(', ', $sets));
            }
        }

        if (Schema::hasTable('payment_vouchers')) {
            DB::table('payment_vouchers')->update([
                'payee' => DB::raw("CONCAT('Demo Payee ', id)"),
            ]);
        }

        if (Schema::hasTable('expense_statement_lines')) {
            DB::table('expense_statement_lines')->update([
                'recipient_name' => DB::raw("IF(recipient_name IS NULL OR recipient_name = '', recipient_name, CONCAT('Demo Recipient ', id))"),
                'vendor_name' => DB::raw("IF(vendor_name IS NULL OR vendor_name = '', vendor_name, CONCAT('Demo Vendor ', id))"),
            ]);
            if (Schema::hasColumn('expense_statement_lines', 'recipient_phone')) {
                DB::statement("UPDATE expense_statement_lines SET recipient_phone = CONCAT('2547', LPAD(MOD(id, 100000000), 8, '0')) WHERE recipient_phone IS NOT NULL AND recipient_phone <> ''");
            }
        }

        if (Schema::hasTable('expense_statement_imports')) {
            $cols = Schema::getColumnListing('expense_statement_imports');
            $upd = [];
            if (in_array('pdf_password', $cols, true)) {
                $upd['pdf_password'] = null;
            }
            if (in_array('account_number', $cols, true)) {
                $upd['account_number'] = '0000000000';
            }
            if ($upd !== []) {
                DB::table('expense_statement_imports')->update($upd);
            }
        }

        if (Schema::hasTable('bank_accounts')) {
            DB::table('bank_accounts')->update([
                'account_number' => DB::raw("CONCAT('DEMOACC', LPAD(id, 4, '0'))"),
            ]);
        }

        if (Schema::hasTable('vendors')) {
            $cols = Schema::getColumnListing('vendors');
            if (in_array('name', $cols, true)) {
                DB::table('vendors')->update(['name' => DB::raw("CONCAT('Demo Vendor ', id)")]);
            }
            if (in_array('phone', $cols, true)) {
                DB::statement("UPDATE vendors SET phone = CONCAT('2547', LPAD(MOD(id, 100000000), 8, '0')) WHERE phone IS NOT NULL");
            }
            if (in_array('email', $cols, true)) {
                DB::statement("UPDATE vendors SET email = CONCAT('vendor', id, '@demo.school') WHERE email IS NOT NULL");
            }
        }
    }

    private function wipeSecrets(): void
    {
        $tables = [
            'personal_access_tokens',
            'password_reset_tokens',
            'sessions',
            'otp_verifications',
            'webauthn_credentials',
            'user_biometric_unlocks',
            'user_device_tokens',
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        foreach ([
            'family_update_links' => 64,
            'family_report_portal_links' => 64,
            'family_receipt_links' => 32,
            'statement_links' => 10,
            'payment_links' => 20,
        ] as $table => $len) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'token')) {
                DB::statement("UPDATE `{$table}` SET `token` = LEFT(MD5(CONCAT('{$table}', id)), {$len})");
            }
        }

        if (Schema::hasTable('report_cards') && Schema::hasColumn('report_cards', 'public_token')) {
            DB::statement("UPDATE report_cards SET public_token = LEFT(MD5(CONCAT('rc', id)), 16)");
        }
    }

    private function applyDemoBranding(): void
    {
        if (Schema::hasTable('settings')) {
            $map = [
                'school_name' => self::SCHOOL_NAME,
                'school_motto' => self::SCHOOL_MOTTO,
                'school_email' => self::SCHOOL_EMAIL,
                'school_phone' => self::SCHOOL_PHONE,
                'school_address' => self::SCHOOL_ADDRESS,
                'school_logo' => null,
                'system_update_url' => 'https://edulynk.co.ke/demo/api/version',
                'staff_id_prefix' => 'DEMO/STAFF/',
                'student_id_prefix' => 'DEMO',
            ];
            foreach ($map as $key => $value) {
                $exists = DB::table('settings')->where('key', $key)->exists();
                if ($exists) {
                    DB::table('settings')->where('key', $key)->update(['value' => $value]);
                } else {
                    DB::table('settings')->insert([
                        'key' => $key,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('website_settings')) {
            $cols = Schema::getColumnListing('website_settings');
            $payload = [];
            foreach (['school_name' => self::SCHOOL_NAME, 'site_name' => self::SCHOOL_NAME, 'tagline' => self::SCHOOL_MOTTO, 'email' => self::SCHOOL_EMAIL, 'phone' => self::SCHOOL_PHONE, 'address' => self::SCHOOL_ADDRESS] as $col => $val) {
                if (in_array($col, $cols, true)) {
                    $payload[$col] = $val;
                }
            }
            if ($payload !== []) {
                DB::table('website_settings')->update($payload);
            }
        }

        if (Schema::hasTable('schools_registry')) {
            $cols = Schema::getColumnListing('schools_registry');
            $payload = [];
            foreach ([
                'name' => self::SCHOOL_NAME,
                'display_name' => self::SCHOOL_NAME,
                'code' => 'DEMO001',
                'contact_email' => self::SCHOOL_EMAIL,
                'contact_phone' => self::SCHOOL_PHONE,
                'api_base_url' => 'https://edulynk.co.ke/demo/api',
            ] as $col => $val) {
                if (in_array($col, $cols, true)) {
                    $payload[$col] = $val;
                }
            }
            if ($payload !== []) {
                DB::table('schools_registry')->update($payload);
            }
        }

        if (Schema::hasTable('communication_templates')) {
            DB::statement(
                "UPDATE communication_templates
                 SET content = REPLACE(REPLACE(content, 'Royal Kings Premier School', ?), 'Royal Kings', ?),
                     title = REPLACE(REPLACE(title, 'Royal Kings Premier School', ?), 'Royal Kings', ?)",
                [self::SCHOOL_NAME, self::SCHOOL_NAME, self::SCHOOL_NAME, self::SCHOOL_NAME]
            );
            if (Schema::hasColumn('communication_templates', 'subject')) {
                DB::statement(
                    "UPDATE communication_templates
                     SET subject = REPLACE(REPLACE(IFNULL(subject, ''), 'Royal Kings Premier School', ?), 'Royal Kings', ?)",
                    [self::SCHOOL_NAME, self::SCHOOL_NAME]
                );
            }
        }
    }

    private function redactLogs(): void
    {
        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')->delete();
        }

        if (Schema::hasTable('communication_logs')) {
            DB::table('communication_logs')->update([
                'message' => '[Redacted for demo]',
            ]);
            if (Schema::hasColumn('communication_logs', 'phone')) {
                DB::statement("UPDATE communication_logs SET phone = CONCAT('2547', LPAD(MOD(id, 100000000), 8, '0')) WHERE phone IS NOT NULL AND phone <> ''");
            }
            if (Schema::hasColumn('communication_logs', 'recipient')) {
                DB::statement("UPDATE communication_logs SET recipient = CONCAT('demo', id, '@demo.school') WHERE recipient LIKE '%@%'");
            }
        }

        if (Schema::hasTable('sms_logs')) {
            DB::table('sms_logs')->update([
                'message' => '[Redacted for demo]',
            ]);
            if (Schema::hasColumn('sms_logs', 'phone_number')) {
                DB::statement("UPDATE sms_logs SET phone_number = CONCAT('2547', LPAD(MOD(id, 100000000), 8, '0'))");
            }
        }

        if (Schema::hasTable('fee_reminders') && Schema::hasColumn('fee_reminders', 'message')) {
            DB::table('fee_reminders')->update(['message' => '[Redacted for demo]']);
        }

        if (Schema::hasTable('communication_job_recipients')) {
            if (Schema::hasColumn('communication_job_recipients', 'name')) {
                DB::table('communication_job_recipients')->update([
                    'name' => DB::raw("CONCAT('Recipient ', id)"),
                ]);
            }
            if (Schema::hasColumn('communication_job_recipients', 'phone')) {
                DB::statement("UPDATE communication_job_recipients SET phone = CONCAT('2547', LPAD(MOD(id, 100000000), 8, '0')) WHERE phone IS NOT NULL");
            }
        }

        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->delete();
        }

        if (Schema::hasTable('phone_number_normalization_logs')) {
            DB::table('phone_number_normalization_logs')->delete();
        }

        if (Schema::hasTable('documents')) {
            DB::table('documents')->update([
                'file_path' => DB::raw("CONCAT('demo/documents/document_', id, '.pdf')"),
                'file_name' => DB::raw("CONCAT('document_', id, '.pdf')"),
            ]);
        }
    }

    private function ensureSuperAdmin(string $email, string $password): void
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $user = User::query()->create([
                'name' => 'Demo Super Admin',
                'email' => $email,
                'phone_number' => '254700000001',
                'password' => Hash::make($password),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'name' => 'Demo Super Admin',
                'password' => Hash::make($password),
                'must_change_password' => false,
                'parent_id' => null,
            ]);
        }

        $role = Role::findOrCreate('Super Admin', 'web');
        if (! $user->hasRole($role)) {
            $user->syncRoles([$role]);
        }
    }

    private function pick(array $pool, int $seed): string
    {
        return $pool[abs($seed) % count($pool)];
    }

    /**
     * Assign a unique first+middle+last combination (case-insensitive).
     * Admission numbers stay unique via DEMO{id}; names must also look distinct in the UI.
     *
     * @param  array<string, true>  $usedFullNames
     * @return array{0: string, 1: string, 2: string}
     */
    private function nextUniqueNameParts(array &$usedFullNames, int &$nameIndex): array
    {
        $firstCount = count($this->firstNames);
        $middleCount = count($this->middleNames);
        $lastCount = count($this->lastNames);
        $capacity = $firstCount * $middleCount * $lastCount;
        $attempts = 0;

        while ($attempts < $capacity + 1000) {
            $i = $nameIndex++;
            $attempts++;

            if ($i < $capacity) {
                $fn = $this->firstNames[$i % $firstCount];
                $mn = $this->middleNames[intdiv($i, $firstCount) % $middleCount];
                $ln = $this->lastNames[intdiv($i, $firstCount * $middleCount) % $lastCount];
            } else {
                $fn = $this->firstNames[$i % $firstCount];
                $mn = $this->middleNames[$i % $middleCount];
                $ln = $this->lastNames[$i % $lastCount].'-'.($i - $capacity + 1);
            }

            $key = Str::lower(trim("{$fn} {$mn} {$ln}"));
            if (! isset($usedFullNames[$key])) {
                $usedFullNames[$key] = true;

                return [$fn, $mn, $ln];
            }
        }

        $fallback = 'Learner'.$nameIndex;
        $usedFullNames[Str::lower($fallback)] = true;

        return [$fallback, 'Demo', 'Student'];
    }

    private function fakePhone(int $seed): string
    {
        return '2547'.str_pad((string) (abs($seed) % 100000000), 8, '0', STR_PAD_LEFT);
    }
}
