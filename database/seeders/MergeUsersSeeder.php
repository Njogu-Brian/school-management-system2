<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MergeUsersSeeder extends Seeder
{
    public function run()
    {
        // Move Admins to Users
        $admins = DB::table('admins')->get();
        foreach ($admins as $admin) {
            DB::table('users')->insert([
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => $admin->password, // Ensure passwords are hashed
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Move Teachers to Users
        $teachers = DB::table('teachers')->get();
        foreach ($teachers as $teacher) {
            DB::table('users')->insert([
                'name' => $teacher->name,
                'email' => $teacher->email,
                'password' => $teacher->password, // Ensure passwords are hashed
                'role' => 'teacher',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
