<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Database\Factories\AdminFactory;

class AdminsTableSeeder extends Seeder
{
    public function run()
    {
        AdminFactory::new()->count(3)->create();
    }
}