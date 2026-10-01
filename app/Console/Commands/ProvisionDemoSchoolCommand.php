<?php

namespace App\Console\Commands;

use App\Services\CpanelMysqlProvisioner;
use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProvisionDemoSchoolCommand extends Command
{
    protected $signature = 'edulynk:provision-demo
                            {--db-name= : MySQL database name (e.g. edulynk_demo)}
                            {--db-user= : MySQL username}
                            {--db-password= : MySQL password}
                            {--db-host=127.0.0.1}
                            {--create-db : Create DB via cPanel UAPI first}
                            {--import : Import storage dump into the database}
                            {--dump= : Path to .sql or .sql.gz (default: configured demo dump)}
                            {--register-only : Only register existing DB in schools_registry}';

    protected $description = 'Import sanitized demo dump and/or register DEMO001 on the control plane';

    public function handle(TenantProvisioningService $provisioning, CpanelMysqlProvisioner $cpanel): int
    {
        $demo = config('edulynk.demo');
        $dbName = $this->option('db-name') ?: 'edulynk_demo';
        $dbUser = $this->option('db-user') ?: $dbName;
        $dbPass = $this->option('db-password');
        $dbHost = $this->option('db-host') ?: '127.0.0.1';

        if ($this->option('create-db')) {
            $this->info('Creating database via cPanel UAPI…');
            $created = $cpanel->createDatabase('demo', $dbPass ?: null);
            $dbName = $created['db_name'];
            $dbUser = $created['db_username'];
            $dbPass = $created['db_password'];
            $this->line("Created {$dbName} / {$dbUser}");
        }

        if ($dbPass === null || $dbPass === '') {
            // Local XAMPP-style empty root remapped for demo user often uses root
            $dbPass = (string) env('DB_PASSWORD', '');
            if (! $this->option('db-user')) {
                $dbUser = (string) env('DB_USERNAME', 'root');
            }
        }

        $creds = [
            'db_name' => $dbName,
            'db_username' => $dbUser,
            'db_password' => $dbPass,
            'db_host' => $dbHost,
            'db_port' => 3306,
        ];

        if ($this->option('import') && ! $this->option('register-only')) {
            $dump = $this->option('dump')
                ?: storage_path('app/'.$demo['dump_relative']);
            if (! File::exists($dump)) {
                $this->error("Dump not found: {$dump}");

                return self::FAILURE;
            }
            $this->info("Importing {$dump} into {$dbName}…");
            $provisioning->importSqlDump($creds, $dump);
            $this->info('Import complete.');
        }

        $school = $provisioning->registerDemoSchool($creds);
        $this->info("Registered {$school->name} ({$school->code}) → {$school->tenantWebUrl()}");
        $this->line('Admin: '.$demo['admin_email'].' / '.$demo['admin_password']);

        return self::SUCCESS;
    }
}
