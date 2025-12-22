<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting; // ✅ make sure you have Setting model

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            'school_name'           => 'Demo School',
            'school_motto'          => 'Learning for All',
            'school_email'          => 'info@demo.school',
            'school_phone'          => '+000 000 000',
            'school_address'        => 'Demo Address',
            'school_logo'           => null,
            'term_start_date'       => '2025-05-01',
            'term_end_date'         => '2025-08-01',
            'timezone'              => 'Africa/Nairobi',
            'currency'              => 'KES',
            'system_version'        => '1.0.0',
            'system_update_url'     => null,
            'show_finance_module'   => 'true',
            'show_transport_module' => 'true',
            'show_kitchen_module'   => 'true',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],   // find by key
                ['value' => $value] // update or insert
            );
        }
    }
}
