<?php

namespace Database\Seeders;

use App\Models\SchoolRegistry;
use Illuminate\Database\Seeder;

class SchoolsRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $apiBase = rtrim((string) config('app.url'), '/').'/api';

        SchoolRegistry::query()->updateOrCreate(
            ['code' => 'RKS001'],
            [
                'name' => 'Royal Kings Schools',
                'slug' => 'royal-kings',
                'api_base_url' => $apiBase,
                'status' => SchoolRegistry::STATUS_ACTIVE,
                'logo_url' => null,
                'primary_color' => '#004A99',
                'contact_email' => null,
                'contact_phone' => null,
                'meta' => [
                    'is_legacy_anchor' => true,
                    'notes' => 'Existing production tenant; seeded as control-plane tenant #1.',
                ],
            ]
        );
    }
}
