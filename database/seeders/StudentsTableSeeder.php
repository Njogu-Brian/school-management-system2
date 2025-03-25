<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use Faker\Factory as Faker;

class StudentsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 10; $i++) { // Seeding 10 students
            Student::create([
                'admission_number' => 'ADM' . str_pad($i, 5, '0', STR_PAD_LEFT), // Example: ADM00001
                'name' => $faker->name,
                'class' => $faker->randomElement(['A', 'B', 'C']),
                'parent_id' => rand(1, 5),
            ]);
        }
    }
}
