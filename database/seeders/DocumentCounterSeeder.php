<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentCounterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    \App\Models\DocumentCounter::firstOrCreate(['type' => 'invoice'], ['next_number' => 1]);
    \App\Models\DocumentCounter::firstOrCreate(['type' => 'receipt'], ['next_number' => 1]);
}
}
