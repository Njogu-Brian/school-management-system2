<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
public function run()
{
    Role::insert([
        ['name' => 'admin'],
        ['name' => 'teacher'],
        ['name' => 'driver'],
        ['name' => 'janitor'],
        ['name' => 'accountant'],
        ['name' => 'receptionist'],
        ['name' => 'chef'],
        ['name' => 'security'],
    ]);
}

}
