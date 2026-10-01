<?php

namespace App\Services;

use App\Models\SchoolPayment;
use App\Models\SchoolRegistry;
use App\Models\SchoolSubscription;
use Carbon\Carbon;
use Database\Seeders\TenantBootstrapSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class TenantProvisioningService
{
    public function __construct(
        private CpanelMysqlProvisioner $cpanel,
        private TenantConnectionManager $connections,
    ) {
    }

    /**
     * @param  array{
     *   name:string,
     *   slug?:string,
     *   code?:string,
     *   contact_email?:string,
     *   contact_phone?:string,
     *   primary_color?:string,
     *   secondary_color?:string,
     *   logo_url?:string,
     *   admin_name:string,
     *   admin_email:string,
     *   admin_phone?:string,
     *   admin_password?:string,
     *   monthly_fee?:float,
     *   db_name?:string,
     *   db_username?:string,
     *   db_password?:string,
     *   db_host?:string,
     *   skip_cpanel?:bool,
     *   seed_classes?:bool,
     * }  $input
     * @return array{school:SchoolRegistry,admin_password:string}
     */
    public function provisionNewSchool(array $input): array
    {
        $name = trim($input['name']);
        $slug = $input['slug'] ?? Str::slug($name);
        $code = isset($input['code'])
            ? SchoolRegistry::normalizeCode($input['code'])
            : $this->generateCode($name);

        if (SchoolRegistry::query()->where('code', $code)->exists()) {
            throw new RuntimeException("School code {$code} already exists.");
        }
        if (SchoolRegistry::query()->where('slug', $slug)->exists()) {
            throw new RuntimeException("Slug {$slug} already exists.");
        }

        $adminPassword = $input['admin_password'] ?? ('Tmp!'.Str::password(12, symbols: false));

        $school = SchoolRegistry::query()->create([
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'api_base_url' => config('edulynk.tenant_base_url').'/'.$slug.'/api',
            'status' => SchoolRegistry::STATUS_PROVISIONING,
            'logo_url' => $input['logo_url'] ?? null,
            'primary_color' => $input['primary_color'] ?? '#0d6efd',
            'secondary_color' => $input['secondary_color'] ?? '#198754',
            'contact_email' => $input['contact_email'] ?? $input['admin_email'],
            'contact_phone' => $input['contact_phone'] ?? ($input['admin_phone'] ?? null),
            'billing_status' => SchoolRegistry::BILLING_TRIAL,
            'monthly_fee' => $input['monthly_fee'] ?? config('edulynk.default_monthly_fee'),
            'next_due_date' => Carbon::now()->addMonth()->startOfMonth()->toDateString(),
            'meta' => ['created_via' => 'operator_wizard'],
        ]);

        try {
            if (! empty($input['db_name']) && ! empty($input['db_username']) && isset($input['db_password'])) {
                $creds = [
                    'db_name' => $input['db_name'],
                    'db_username' => $input['db_username'],
                    'db_password' => $input['db_password'],
                    'db_host' => $input['db_host'] ?? '127.0.0.1',
                    'db_port' => 3306,
                ];
            } elseif (! empty($input['skip_cpanel'])) {
                throw new RuntimeException('Database credentials are required when cPanel UAPI is skipped.');
            } else {
                $creds = $this->cpanel->createDatabase($slug);
            }

            $school->forceFill([
                'db_name' => $creds['db_name'],
                'db_username' => $creds['db_username'],
                'db_password_encrypted' => Crypt::encryptString($creds['db_password']),
                'db_host' => $creds['db_host'] ?? '127.0.0.1',
                'db_port' => $creds['db_port'] ?? 3306,
            ])->save();

            $this->connections->configureTemporary($creds, 'tenant');
            $this->runMigrationsOn('tenant');

            $previous = config('database.default');
            config(['database.default' => 'tenant']);
            try {
                (new TenantBootstrapSeeder)->run([
                    'school_name' => $name,
                    'school_email' => $input['contact_email'] ?? $input['admin_email'],
                    'school_phone' => $input['contact_phone'] ?? null,
                    'primary_color' => $input['primary_color'] ?? '#0d6efd',
                    'secondary_color' => $input['secondary_color'] ?? '#198754',
                    'admin_name' => $input['admin_name'],
                    'admin_email' => $input['admin_email'],
                    'admin_phone' => $input['admin_phone'] ?? null,
                    'admin_password' => $adminPassword,
                    'seed_classes' => $input['seed_classes'] ?? true,
                ]);
            } finally {
                config(['database.default' => $previous]);
            }

            try {
                $this->cpanel->wirePublicHtmlSlug($slug);
            } catch (\Throwable $e) {
                // Non-fatal if paths not configured
                report($e);
            }

            $school->forceFill([
                'status' => SchoolRegistry::STATUS_ACTIVE,
                'provisioned_at' => now(),
            ])->save();

            $this->ensureSubscriptionPeriod($school, Carbon::now()->format('Y-m'));

            return ['school' => $school->fresh(), 'admin_password' => $adminPassword];
        } catch (\Throwable $e) {
            $school->forceFill(['status' => SchoolRegistry::STATUS_SUSPENDED, 'billing_status' => SchoolRegistry::BILLING_SUSPENDED])->save();
            throw $e;
        } finally {
            $this->connections->restoreDefault();
        }
    }

    /**
     * Register an already-imported demo database as school #1.
     *
     * @param  array{db_name:string,db_username:string,db_password:string,db_host?:string,db_port?:int}  $creds
     */
    public function registerDemoSchool(array $creds): SchoolRegistry
    {
        $demo = config('edulynk.demo');
        $code = $demo['code'];
        $slug = $demo['slug'];

        $school = SchoolRegistry::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $demo['name'],
                'slug' => $slug,
                'api_base_url' => config('edulynk.tenant_base_url').'/'.$slug.'/api',
                'status' => SchoolRegistry::STATUS_ACTIVE,
                'primary_color' => '#0d6efd',
                'secondary_color' => '#198754',
                'contact_email' => 'demo@edulynk.co.ke',
                'contact_phone' => '+254700000000',
                'db_name' => $creds['db_name'],
                'db_username' => $creds['db_username'],
                'db_password_encrypted' => Crypt::encryptString($creds['db_password']),
                'db_host' => $creds['db_host'] ?? '127.0.0.1',
                'db_port' => $creds['db_port'] ?? 3306,
                'billing_status' => SchoolRegistry::BILLING_TRIAL,
                'monthly_fee' => $demo['monthly_fee'],
                'next_due_date' => Carbon::now()->addMonth()->startOfMonth()->toDateString(),
                'provisioned_at' => now(),
                'meta' => ['source' => 'demo_dump'],
            ]
        );

        $this->refreshStudentCount($school);
        $this->ensureSubscriptionPeriod($school, Carbon::now()->format('Y-m'));

        try {
            $this->cpanel->wirePublicHtmlSlug($slug);
        } catch (\Throwable $e) {
            report($e);
        }

        return $school;
    }

    public function ensureSubscriptionPeriod(SchoolRegistry $school, string $period): SchoolSubscription
    {
        return SchoolSubscription::query()->firstOrCreate(
            [
                'school_registry_id' => $school->id,
                'period' => $period,
            ],
            [
                'amount_due' => $school->monthly_fee,
                'amount_paid' => 0,
                'status' => ((float) $school->monthly_fee) <= 0 ? 'waived' : 'open',
                'due_date' => Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString(),
            ]
        );
    }

    public function recordPayment(
        SchoolRegistry $school,
        float $amount,
        string $method,
        ?string $reference = null,
        ?string $period = null,
        ?int $recordedBy = null,
        ?string $notes = null,
    ): SchoolPayment {
        $period = $period ?: Carbon::now()->format('Y-m');
        $sub = $this->ensureSubscriptionPeriod($school, $period);

        $payment = SchoolPayment::query()->create([
            'school_registry_id' => $school->id,
            'school_subscription_id' => $sub->id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference,
            'paid_at' => now(),
            'recorded_by' => $recordedBy,
            'notes' => $notes,
        ]);

        $sub->amount_paid = (float) $sub->amount_paid + $amount;
        $sub->refreshStatus();

        if ($sub->status === 'paid') {
            $school->forceFill([
                'billing_status' => SchoolRegistry::BILLING_CURRENT,
                'next_due_date' => Carbon::createFromFormat('Y-m', $period)->addMonth()->endOfMonth()->toDateString(),
            ])->save();
        } elseif ($sub->status === 'overdue' || $sub->status === 'partial') {
            $school->forceFill([
                'billing_status' => $sub->status === 'overdue'
                    ? SchoolRegistry::BILLING_OVERDUE
                    : SchoolRegistry::BILLING_CURRENT,
            ])->save();
        }

        return $payment;
    }

    public function suspend(SchoolRegistry $school): void
    {
        $school->forceFill([
            'status' => SchoolRegistry::STATUS_SUSPENDED,
            'billing_status' => SchoolRegistry::BILLING_SUSPENDED,
        ])->save();
    }

    public function activate(SchoolRegistry $school): void
    {
        $school->forceFill([
            'status' => SchoolRegistry::STATUS_ACTIVE,
            'billing_status' => SchoolRegistry::BILLING_CURRENT,
        ])->save();
    }

    public function refreshStudentCount(SchoolRegistry $school): int
    {
        if (! $school->db_name) {
            return 0;
        }

        try {
            $this->connections->configureFromSchool($school);
            $count = (int) DB::connection('tenant')->table('students')->where('archive', 0)->count();
            $school->forceFill(['student_count_cached' => $count])->save();

            return $count;
        } catch (\Throwable) {
            return (int) $school->student_count_cached;
        } finally {
            $this->connections->restoreDefault();
        }
    }

    public function importSqlDump(array $creds, string $sqlGzOrSqlPath): void
    {
        if (! is_file($sqlGzOrSqlPath)) {
            throw new RuntimeException("Dump not found: {$sqlGzOrSqlPath}");
        }

        $sqlPath = $sqlGzOrSqlPath;
        $tmp = null;
        if (str_ends_with(strtolower($sqlGzOrSqlPath), '.gz')) {
            $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'edulynk_import_'.uniqid().'.sql';
            $in = gzopen($sqlGzOrSqlPath, 'rb');
            if ($in === false) {
                throw new RuntimeException('Unable to open gzip dump.');
            }
            $out = fopen($tmp, 'wb');
            while (! gzeof($in)) {
                fwrite($out, gzread($in, 1024 * 512));
            }
            fclose($out);
            gzclose($in);
            $sqlPath = $tmp;
        }

        $mysql = $this->findMysqlBinary();
        $host = $creds['db_host'] ?? '127.0.0.1';
        $port = (string) ($creds['db_port'] ?? 3306);
        $user = $creds['db_username'];
        $pass = $creds['db_password'];
        $db = $creds['db_name'];

        $cmd = [
            $mysql,
            '--host='.$host,
            '--port='.$port,
            '-u'.$user,
            '--max_allowed_packet=512M',
            $db,
        ];
        if ($pass !== '') {
            array_splice($cmd, 4, 0, ['-p'.$pass]);
        }

        $process = new Process($cmd);
        $process->setTimeout(600);
        $process->setInput(file_get_contents($sqlPath));
        $process->run();

        if ($tmp && is_file($tmp)) {
            @unlink($tmp);
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('mysql import failed: '.$process->getErrorOutput());
        }
    }

    private function runMigrationsOn(string $connection): void
    {
        Artisan::call('migrate', [
            '--database' => $connection,
            '--force' => true,
            '--path' => 'database/migrations',
        ]);
    }

    private function generateCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'SCH', 0, 3));
        do {
            $code = $prefix.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
        } while (SchoolRegistry::query()->where('code', $code)->exists());

        return $code;
    }

    private function findMysqlBinary(): string
    {
        $candidates = [
            env('MYSQL_PATH'),
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'mysql',
        ];
        foreach ($candidates as $c) {
            if ($c && (is_file($c) || $c === 'mysql')) {
                return $c;
            }
        }

        return 'mysql';
    }
}
