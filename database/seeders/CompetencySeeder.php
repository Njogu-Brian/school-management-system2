<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\Competency;
use App\Models\Academics\CBCSubstrand;
use Illuminate\Support\Facades\DB;

class CompetencySeeder extends Seeder
{
    public function run(): void
    {
        // Get sample substrands to attach competencies to
        $substrands = CBCSubstrand::with('strand')->take(10)->get();

        if ($substrands->isEmpty()) {
            $this->command->warn('No substrands found. Please seed CBC strands and substrands first.');
            return;
        }

        $competencies = [
            // Generic competencies that can be applied to multiple substrands
            [
                'code' => 'C1',
                'name' => 'Communication and Collaboration',
                'description' => 'Learners can effectively communicate and collaborate with others',
                'indicators' => [
                    'Expresses ideas clearly',
                    'Listens actively to others',
                    'Works well in groups',
                    'Respects different opinions',
                ],
                'assessment_criteria' => [
                    'Clear articulation of ideas',
                    'Active participation in discussions',
                    'Effective teamwork',
                ],
                'competency_level' => 'developing',
            ],
            [
                'code' => 'C2',
                'name' => 'Critical Thinking and Problem Solving',
                'description' => 'Learners can think critically and solve problems',
                'indicators' => [
                    'Analyzes information carefully',
                    'Identifies problems',
                    'Generates solutions',
                    'Evaluates options',
                ],
                'assessment_criteria' => [
                    'Logical reasoning',
                    'Creative problem-solving',
                    'Evaluation of solutions',
                ],
                'competency_level' => 'developing',
            ],
            [
                'code' => 'C3',
                'name' => 'Creativity and Innovation',
                'description' => 'Learners demonstrate creativity and innovation',
                'indicators' => [
                    'Generates original ideas',
                    'Thinks outside the box',
                    'Uses imagination',
                    'Innovates solutions',
                ],
                'assessment_criteria' => [
                    'Originality of ideas',
                    'Creative expression',
                    'Innovative approaches',
                ],
                'competency_level' => 'beginning',
            ],
            [
                'code' => 'C4',
                'name' => 'Digital Literacy',
                'description' => 'Learners can use digital tools effectively',
                'indicators' => [
                    'Uses digital devices',
                    'Navigates digital platforms',
                    'Creates digital content',
                    'Uses digital tools for learning',
                ],
                'assessment_criteria' => [
                    'Proficiency with digital tools',
                    'Digital content creation',
                    'Safe digital practices',
                ],
                'competency_level' => 'beginning',
            ],
            [
                'code' => 'C5',
                'name' => 'Self-efficacy',
                'description' => 'Learners demonstrate self-confidence and self-efficacy',
                'indicators' => [
                    'Believes in own abilities',
                    'Takes initiative',
                    'Persists in challenges',
                    'Sets and achieves goals',
                ],
                'assessment_criteria' => [
                    'Self-confidence',
                    'Initiative-taking',
                    'Persistence',
                ],
                'competency_level' => 'developing',
            ],
            [
                'code' => 'C6',
                'name' => 'Learning to Learn',
                'description' => 'Learners can learn independently and effectively',
                'indicators' => [
                    'Sets learning goals',
                    'Uses learning strategies',
                    'Reflects on learning',
                    'Seeks help when needed',
                ],
                'assessment_criteria' => [
                    'Goal-setting',
                    'Strategy use',
                    'Reflection',
                ],
                'competency_level' => 'developing',
            ],
            [
                'code' => 'C7',
                'name' => 'Imagination and Creativity',
                'description' => 'Learners use imagination and creativity',
                'indicators' => [
                    'Uses imagination',
                    'Creates original works',
                    'Thinks creatively',
                    'Expresses creatively',
                ],
                'assessment_criteria' => [
                    'Imaginative thinking',
                    'Creative expression',
                    'Originality',
                ],
                'competency_level' => 'beginning',
            ],
        ];

        foreach ($substrands as $index => $substrand) {
            // Attach 2-3 competencies to each substrand
            $competencyCount = min(3, count($competencies));
            $selectedCompetencies = array_slice($competencies, 0, $competencyCount);

            foreach ($selectedCompetencies as $compIndex => $comp) {
                Competency::updateOrCreate(
                    [
                        'substrand_id' => $substrand->id,
                        'code' => $comp['code'] . '.' . ($compIndex + 1),
                    ],
                    array_merge($comp, [
                        'substrand_id' => $substrand->id,
                        'display_order' => $compIndex + 1,
                        'is_active' => true,
                    ])
                );
            }
        }

        $this->command->info('Competencies seeded successfully.');
    }
}

