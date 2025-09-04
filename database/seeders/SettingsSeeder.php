<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting; // ✅ make sure you have Setting model

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            'school_name'           => 'Royal Kings School',
            'school_motto'          => 'Building a Sure Foundation',
            'school_email'          => 'info@royalkingsschools.sc.ke',
            'school_phone'          => '+254 708 225 397',
            'school_address'        => 'Riverside, Lower Kabete, Nairobi',
            'school_logo'           => 'uploads/logo.png',
            'term_start_date'       => '2025-05-01',
            'term_end_date'         => '2025-08-01',
            'timezone'              => 'Africa/Nairobi',
            'currency'              => 'KES',
            'system_version'        => '1.0.0',
            'system_update_url'     => 'https://royalkingsschools.sc.ke/api/version',
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
