<?php

namespace App\Console\Commands;

use App\Models\SchoolRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RegisterSchoolCommand extends Command
{
    protected $signature = 'schools:register
                            {name : Display name of the school}
                            {--code= : Unique school code (auto-generated if omitted)}
                            {--slug= : URL slug (derived from name if omitted)}
                            {--api-base-url= : Tenant API base URL ending in /api}
                            {--status=active : active|suspended|provisioning}
                            {--primary-color= : Optional #RRGGBB}
                            {--logo-url= : Optional logo URL}';

    protected $description = 'Register a school in the control-plane schools_registry';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $slug = $this->option('slug') ?: Str::slug($name);
        $code = $this->option('code')
            ? SchoolRegistry::normalizeCode((string) $this->option('code'))
            : $this->generateCode($name);

        $apiBase = $this->option('api-base-url')
            ? rtrim((string) $this->option('api-base-url'), '/')
            : rtrim((string) config('app.url'), '/').'/api';

        if (! str_ends_with($apiBase, '/api')) {
            $this->warn('api-base-url usually ends with /api (mobile clients append paths like /login).');
        }

        if (SchoolRegistry::query()->where('code', $code)->exists()) {
            $this->error("Code {$code} already exists.");

            return self::FAILURE;
        }

        if (SchoolRegistry::query()->where('slug', $slug)->exists()) {
            $this->error("Slug {$slug} already exists.");

            return self::FAILURE;
        }

        $school = SchoolRegistry::query()->create([
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'api_base_url' => $apiBase,
            'status' => (string) $this->option('status'),
            'logo_url' => $this->option('logo-url') ?: null,
            'primary_color' => $this->option('primary-color') ?: null,
        ]);

        $this->info("Registered {$school->name}");
        $this->table(
            ['Code', 'Slug', 'API', 'Status'],
            [[$school->code, $school->slug, $school->api_base_url, $school->status]]
        );

        return self::SUCCESS;
    }

    private function generateCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'SCH', 0, 3));
        do {
            $code = $prefix.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
        } while (SchoolRegistry::query()->where('code', $code)->exists());

        return $code;
    }
}
