<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Behaviour;
use Illuminate\Support\Facades\DB;

class BehaviourSeeder extends Seeder
{
    public function run(): void
    {
        // Disable FK checks to safely delete existing records
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Behaviour::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $behaviours = [
            // Positive
            ['name' => 'Punctuality', 'type' => 'positive', 'description' => 'Arrives on time for class.'],
            ['name' => 'Respectfulness', 'type' => 'positive', 'description' => 'Shows respect for others.'],
            ['name' => 'Teamwork', 'type' => 'positive', 'description' => 'Works well with others.'],
            ['name' => 'Honesty', 'type' => 'positive', 'description' => 'Is truthful and reliable.'],
            ['name' => 'Leadership', 'type' => 'positive', 'description' => 'Shows initiative and leads responsibly.'],

            // Negative
            ['name' => 'Disobedience', 'type' => 'negative', 'description' => 'Fails to follow school rules.'],
            ['name' => 'Disruptive Behaviour', 'type' => 'negative', 'description' => 'Interrupts lessons or distracts others.'],
            ['name' => 'Bullying', 'type' => 'negative', 'description' => 'Displays unkind or aggressive behaviour.'],
            ['name' => 'Lateness', 'type' => 'negative', 'description' => 'Frequently arrives late.'],
            ['name' => 'Untidiness', 'type' => 'negative', 'description' => 'Fails to maintain cleanliness.'],
        ];

        Behaviour::insert($behaviours);

        $this->command->info('✅ Behaviour categories seeded successfully.');
    }
}
