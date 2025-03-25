<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Factories\ParentInfoFactory;

class ParentInfoTableSeeder extends Seeder
{
    public function run()
    {
        ParentInfoFactory::new()->count(10)->create();
    }
}