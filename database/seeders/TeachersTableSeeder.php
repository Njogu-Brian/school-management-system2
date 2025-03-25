<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Database\Factories\TeacherFactory;

class TeachersTableSeeder extends Seeder
{
    public function run()
    {
        TeacherFactory::new()->count(5)->create();
    }
}