<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;

class CBCSubstrandSeeder extends Seeder
{
    public function run(): void
    {
        // Get strands and create sample substrands
        $strands = CBCStrand::where('is_active', true)->get();

        foreach ($strands as $strand) {
            // Create 2-4 substrands per strand
            $substrandCount = rand(2, 4);
            
            for ($i = 1; $i <= $substrandCount; $i++) {
                $code = $strand->code . '.' . $i;
                
                CBCSubstrand::updateOrCreate(
                    ['strand_id' => $strand->id, 'code' => $code],
                    [
                        'name' => "{$strand->name} - Substrand {$i}",
                        'description' => "Substrand {$i} for {$strand->name}",
                        'learning_outcomes' => [
                            "Learner will demonstrate understanding of {$strand->name} concepts",
                            "Learner will apply knowledge in practical situations",
                        ],
                        'key_inquiry_questions' => [
                            "What is the importance of {$strand->name}?",
                            "How can we apply {$strand->name} in daily life?",
                        ],
                        'core_competencies' => ['CC', 'CTPS', 'CI'],
                        'values' => ['Respect', 'Responsibility', 'Unity'],
                        'suggested_lessons' => rand(3, 6),
                        'display_order' => $i,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('CBC Substrands seeded successfully.');
    }
}
