<?php

namespace App\Console\Commands;

use Database\Seeders\TenantBootstrapSeeder;
use Illuminate\Console\Command;

class TenantBootstrapCommand extends Command
{
    protected $signature = 'tenant:bootstrap
                            {--school-name=New School}
                            {--school-email=info@school.test}
                            {--school-phone=}
                            {--admin-name=School Admin}
                            {--admin-email=}
                            {--admin-phone=}
                            {--admin-password=}
                            {--primary-color=#0d6efd}
                            {--secondary-color=#198754}
                            {--no-classes : Skip Grade 1-9 / streams seed}';

    protected $description = 'Bootstrap an empty tenant ERP (roles, settings, Super Admin, classes)';

    public function handle(): int
    {
        $email = $this->option('admin-email');
        if (! $email) {
            $this->error('--admin-email is required');

            return self::FAILURE;
        }

        $password = $this->option('admin-password') ?: ('Tmp!'.bin2hex(random_bytes(4)));

        (new TenantBootstrapSeeder)->run([
            'school_name' => $this->option('school-name'),
            'school_email' => $this->option('school-email'),
            'school_phone' => $this->option('school-phone') ?: null,
            'admin_name' => $this->option('admin-name'),
            'admin_email' => $email,
            'admin_phone' => $this->option('admin-phone') ?: null,
            'admin_password' => $password,
            'primary_color' => $this->option('primary-color'),
            'secondary_color' => $this->option('secondary-color'),
            'seed_classes' => ! $this->option('no-classes'),
        ]);

        $this->info('Tenant bootstrap complete.');
        $this->line("Admin: {$email}");
        $this->line("Temp password: {$password}");

        return self::SUCCESS;
    }
}
