<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            ['key' => 'school_name', 'value' => 'Royal Kings School'],
            ['key' => 'school_motto', 'value' => 'Building a Sure Foundation'],
            ['key' => 'school_email', 'value' => 'info@royalkingsschools.sc.ke'],
            ['key' => 'school_phone', 'value' => '+254 708 225 397'],
            ['key' => 'school_address', 'value' => 'Riverside, Lower Kabete, Nairobi'],
            ['key' => 'school_logo', 'value' => 'uploads/logo.png'],
            ['key' => 'term_start_date', 'value' => '2025-05-01'],
            ['key' => 'term_end_date', 'value' => '2025-08-01'],
            ['key' => 'timezone', 'value' => 'Africa/Nairobi'],
            ['key' => 'currency', 'value' => 'KES'],
            ['key' => 'system_version', 'value' => '1.0.0'],
            ['key' => 'system_update_url', 'value' => 'https://royalkingsschools.sc.ke/api/version'],
            ['key' => 'show_finance_module', 'value' => 'true'],
            ['key' => 'show_transport_module', 'value' => 'true'],
            ['key' => 'show_kitchen_module', 'value' => 'true'],
        ];

        DB::table('settings')->insert($settings);
    }
}
