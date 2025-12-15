<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VoteheadCategory;

class VoteheadCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Tuition', 'description' => 'Core academic tuition fees', 'display_order' => 1],
            ['name' => 'Boarding', 'description' => 'Hostel and accommodation fees', 'display_order' => 2],
            ['name' => 'Transport', 'description' => 'School bus and transportation fees', 'display_order' => 3],
            ['name' => 'Library', 'description' => 'Library and book fees', 'display_order' => 4],
            ['name' => 'Sports', 'description' => 'Sports and games fees', 'display_order' => 5],
            ['name' => 'Laboratory', 'description' => 'Science lab and equipment fees', 'display_order' => 6],
            ['name' => 'Computer', 'description' => 'Computer and IT fees', 'display_order' => 7],
            ['name' => 'Medical', 'description' => 'Medical and health fees', 'display_order' => 8],
            ['name' => 'Development', 'description' => 'School development and infrastructure fees', 'display_order' => 9],
            ['name' => 'Activity', 'description' => 'Co-curricular activities fees', 'display_order' => 10],
            ['name' => 'Examination', 'description' => 'Exam and assessment fees', 'display_order' => 11],
            ['name' => 'Administrative', 'description' => 'Administrative and processing fees', 'display_order' => 12],
            ['name' => 'Uniform', 'description' => 'School uniform and attire fees', 'display_order' => 13],
            ['name' => 'Stationery', 'description' => 'Stationery and learning materials', 'display_order' => 14],
            ['name' => 'Other', 'description' => 'Other miscellaneous fees', 'display_order' => 99],
        ];

        foreach ($categories as $category) {
            VoteheadCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}

