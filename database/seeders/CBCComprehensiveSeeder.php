<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academics\LearningArea;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\Competency;
use Illuminate\Support\Facades\DB;

class CBCComprehensiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Comprehensive CBC curriculum data for all levels
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $this->command->info('Seeding comprehensive CBC curriculum data...');

            // Seed Learning Areas
            $this->seedLearningAreas();
            $this->command->info('✓ Learning Areas seeded');

            // Seed Strands
            $this->seedStrands();
            $this->command->info('✓ Strands seeded');

            // Seed Substrands
            $this->seedSubstrands();
            $this->command->info('✓ Substrands seeded');

            // Seed Competencies
            $this->seedCompetencies();
            $this->command->info('✓ Competencies seeded');

            DB::commit();
            $this->command->info('CBC curriculum data seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding CBC curriculum: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Seed Learning Areas for all levels
     */
    protected function seedLearningAreas(): void
    {
        $learningAreas = [
            // Pre-Primary
            [
                'code' => 'LA-PP',
                'name' => 'Language Activities',
                'description' => 'Language Activities for Pre-Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2'],
                'display_order' => 1,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MA-PP',
                'name' => 'Mathematics Activities',
                'description' => 'Mathematics Activities for Pre-Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2'],
                'display_order' => 2,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'EA-PP',
                'name' => 'Environmental Activities',
                'description' => 'Environmental Activities for Pre-Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2'],
                'display_order' => 3,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'RA-PP',
                'name' => 'Religious Activities',
                'description' => 'Religious Activities for Pre-Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2'],
                'display_order' => 4,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'PA-PP',
                'name' => 'Psychomotor and Creative Activities',
                'description' => 'Psychomotor and Creative Activities for Pre-Primary',
                'level_category' => 'Pre-Primary',
                'levels' => ['PP1', 'PP2'],
                'display_order' => 5,
                'is_core' => true,
                'is_active' => true,
            ],

            // Lower Primary (already exists, but we'll ensure they're there)
            // Upper Primary (already exists)
            // Junior Secondary
            [
                'code' => 'ENG-JS',
                'name' => 'English',
                'description' => 'English Language for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 1,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'KIS-JS',
                'name' => 'Kiswahili',
                'description' => 'Kiswahili Language for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 2,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MATH-JS',
                'name' => 'Mathematics',
                'description' => 'Mathematics for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 3,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SCI-JS',
                'name' => 'Integrated Science',
                'description' => 'Integrated Science for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 4,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SS-JS',
                'name' => 'Social Studies',
                'description' => 'Social Studies for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 5,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CRE-JS',
                'name' => 'Christian Religious Education',
                'description' => 'Christian Religious Education for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 6,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'IRE-JS',
                'name' => 'Islamic Religious Education',
                'description' => 'Islamic Religious Education for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 7,
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BCA-JS',
                'name' => 'Business Studies',
                'description' => 'Business Studies for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 8,
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AGR-JS',
                'name' => 'Agriculture',
                'description' => 'Agriculture for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 9,
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'code' => 'HSC-JS',
                'name' => 'Home Science',
                'description' => 'Home Science for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 10,
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'code' => 'ART-JS',
                'name' => 'Arts and Sports',
                'description' => 'Arts and Sports for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 11,
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'code' => 'LS-JS',
                'name' => 'Life Skills Education',
                'description' => 'Life Skills Education for Junior Secondary',
                'level_category' => 'Junior Secondary',
                'levels' => ['Grade 7', 'Grade 8', 'Grade 9'],
                'display_order' => 12,
                'is_core' => true,
                'is_active' => true,
            ],
        ];

        foreach ($learningAreas as $area) {
            LearningArea::updateOrCreate(
                ['code' => $area['code'], 'level_category' => $area['level_category']],
                $area
            );
        }
    }

    /**
     * Seed comprehensive strands for all learning areas
     */
    protected function seedStrands(): void
    {
        // Lower Primary English
        $engLP = LearningArea::where('code', 'ENG')->where('level_category', 'Lower Primary')->first();
        if ($engLP) {
            $this->seedEnglishStrands($engLP, ['Grade 1', 'Grade 2', 'Grade 3']);
        }

        // Lower Primary Mathematics
        $mathLP = LearningArea::where('code', 'MATH')->where('level_category', 'Lower Primary')->first();
        if ($mathLP) {
            $this->seedMathematicsStrands($mathLP, ['Grade 1', 'Grade 2', 'Grade 3']);
        }

        // Upper Primary English
        $engUP = LearningArea::where('code', 'ENG')->where('level_category', 'Upper Primary')->first();
        if ($engUP) {
            $this->seedEnglishStrands($engUP, ['Grade 4', 'Grade 5', 'Grade 6']);
        }

        // Upper Primary Mathematics
        $mathUP = LearningArea::where('code', 'MATH')->where('level_category', 'Upper Primary')->first();
        if ($mathUP) {
            $this->seedMathematicsStrands($mathUP, ['Grade 4', 'Grade 5', 'Grade 6']);
        }

        // Junior Secondary English
        $engJS = LearningArea::where('code', 'ENG-JS')->where('level_category', 'Junior Secondary')->first();
        if ($engJS) {
            $this->seedEnglishStrands($engJS, ['Grade 7', 'Grade 8', 'Grade 9']);
        }

        // Junior Secondary Mathematics
        $mathJS = LearningArea::where('code', 'MATH-JS')->where('level_category', 'Junior Secondary')->first();
        if ($mathJS) {
            $this->seedMathematicsStrands($mathJS, ['Grade 7', 'Grade 8', 'Grade 9']);
        }
    }

    /**
     * Seed English strands
     */
    protected function seedEnglishStrands(LearningArea $learningArea, array $levels): void
    {
        $strands = [
            [
                'name' => 'Listening and Speaking',
                'description' => 'Develop listening and speaking skills',
                'display_order' => 1,
            ],
            [
                'name' => 'Reading',
                'description' => 'Develop reading skills and comprehension',
                'display_order' => 2,
            ],
            [
                'name' => 'Writing',
                'description' => 'Develop writing skills',
                'display_order' => 3,
            ],
            [
                'name' => 'Grammar',
                'description' => 'Develop understanding of grammar rules',
                'display_order' => 4,
            ],
        ];

        foreach ($levels as $level) {
            foreach ($strands as $index => $strand) {
                // Create unique code: e.g., ENG.G1.S1, ENG.G2.S1
                $levelNum = preg_replace('/[^0-9]/', '', $level) ?: str_replace(['PP'], '', $level);
                if (empty($levelNum)) {
                    $levelNum = substr($level, -1);
                }
                $code = $learningArea->code . '.G' . $levelNum . '.S' . ($index + 1);
                
                CBCStrand::updateOrCreate(
                    [
                        'code' => $code,
                        'level' => $level,
                        'learning_area_id' => $learningArea->id,
                    ],
                    array_merge($strand, [
                        'learning_area' => $learningArea->name,
                        'is_active' => true,
                    ])
                );
            }
        }
    }

    /**
     * Seed Mathematics strands
     */
    protected function seedMathematicsStrands(LearningArea $learningArea, array $levels): void
    {
        $strands = [
            [
                'name' => 'Numbers',
                'description' => 'Number concepts and operations',
                'display_order' => 1,
            ],
            [
                'name' => 'Measurement',
                'description' => 'Measurement concepts and skills',
                'display_order' => 2,
            ],
            [
                'name' => 'Geometry',
                'description' => 'Geometric shapes and spatial reasoning',
                'display_order' => 3,
            ],
            [
                'name' => 'Data Handling',
                'description' => 'Data collection, organization, and interpretation',
                'display_order' => 4,
            ],
            [
                'name' => 'Algebra',
                'description' => 'Algebraic thinking and patterns',
                'display_order' => 5,
            ],
        ];

        foreach ($levels as $level) {
            foreach ($strands as $index => $strand) {
                // Create unique code: e.g., MATH.G1.S1, MATH.G2.S1
                $levelNum = preg_replace('/[^0-9]/', '', $level) ?: str_replace(['PP'], '', $level);
                if (empty($levelNum)) {
                    $levelNum = substr($level, -1);
                }
                $code = $learningArea->code . '.G' . $levelNum . '.S' . ($index + 1);
                
                CBCStrand::updateOrCreate(
                    [
                        'code' => $code,
                        'level' => $level,
                        'learning_area_id' => $learningArea->id,
                    ],
                    array_merge($strand, [
                        'learning_area' => $learningArea->name,
                        'is_active' => true,
                    ])
                );
            }
        }
    }

    /**
     * Seed substrands for all strands
     */
    protected function seedSubstrands(): void
    {
        $strands = CBCStrand::where('is_active', true)->get();

        foreach ($strands as $strand) {
            // Create 2-4 substrands per strand based on strand type
            $substrandCount = $this->getSubstrandCount($strand->name);
            
            for ($i = 1; $i <= $substrandCount; $i++) {
                $code = $strand->code . '.' . $i;
                $name = $this->getSubstrandName($strand->name, $i);
                
                CBCSubstrand::updateOrCreate(
                    [
                        'strand_id' => $strand->id,
                        'code' => $code,
                    ],
                    [
                        'name' => $name,
                        'description' => "{$name} for {$strand->name} in {$strand->level}",
                        'learning_outcomes' => $this->getLearningOutcomes($strand->name, $i),
                        'key_inquiry_questions' => $this->getKeyInquiryQuestions($strand->name, $i),
                        'core_competencies' => ['Communication and Collaboration', 'Critical Thinking and Problem Solving', 'Creativity and Innovation'],
                        'values' => ['Respect', 'Responsibility', 'Unity', 'Peace', 'Love'],
                        'pclc' => ['Parental engagement', 'Community involvement', 'Learner participation'],
                        'suggested_lessons' => rand(3, 6),
                        'display_order' => $i,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Get substrand count based on strand name
     */
    protected function getSubstrandCount(string $strandName): int
    {
        $counts = [
            'Listening and Speaking' => 4,
            'Reading' => 4,
            'Writing' => 4,
            'Grammar' => 3,
            'Kusikiliza na Kuzungumza' => 4,
            'Kusoma' => 4,
            'Kuandika' => 4,
            'Sarufi' => 3,
            'Numbers' => 5,
            'Measurement' => 4,
            'Geometry' => 3,
            'Data Handling' => 3,
            'Algebra' => 3,
            'Environmental Awareness' => 4,
            'Living Things' => 4,
            'Non-Living Things' => 4,
            'Conservation' => 4,
            'Scientific Inquiry' => 4,
            'Life Sciences' => 4,
            'Physical Sciences' => 4,
            'Earth Sciences' => 4,
            'History' => 4,
            'Geography' => 4,
            'Citizenship' => 4,
            'Culture' => 4,
        ];

        return $counts[$strandName] ?? 3;
    }

    /**
     * Get substrand name based on strand and index
     */
    protected function getSubstrandName(string $strandName, int $index): string
    {
        $names = [
            'Listening and Speaking' => [
                'Listening for Comprehension',
                'Speaking Fluently',
                'Pronunciation and Intonation',
                'Conversation Skills',
            ],
            'Reading' => [
                'Word Recognition',
                'Reading Comprehension',
                'Reading Fluency',
                'Reading for Information',
            ],
            'Writing' => [
                'Handwriting',
                'Creative Writing',
                'Composition Writing',
                'Writing Mechanics',
            ],
            'Grammar' => [
                'Parts of Speech',
                'Sentence Structure',
                'Grammar Rules',
            ],
            'Numbers' => [
                'Number Recognition',
                'Number Operations',
                'Place Value',
                'Fractions',
                'Decimals',
            ],
            'Measurement' => [
                'Length',
                'Weight',
                'Capacity',
                'Time',
            ],
            'Geometry' => [
                'Shapes',
                'Spatial Reasoning',
                'Angles',
            ],
            'Data Handling' => [
                'Data Collection',
                'Data Organization',
                'Data Interpretation',
            ],
            'Algebra' => [
                'Patterns',
                'Equations',
                'Variables',
            ],
            'Kusikiliza na Kuzungumza' => [
                'Kusikiliza Kwa Umakini',
                'Kuzungumza Kwa Ufasaha',
                'Matamshi na Matoneo',
                'Mazungumzo',
            ],
            'Kusoma' => [
                'Kutambua Maneno',
                'Kuelewa Kusoma',
                'Kusoma Kwa Ufasaha',
                'Kusoma Taarifa',
            ],
            'Kuandika' => [
                'Kuandika Kwa Mikono',
                'Kuandika Kwa Ubunifu',
                'Kuandika Insha',
                'Kanuni za Kuandika',
            ],
            'Sarufi' => [
                'Sehemu za Maneno',
                'Muundo wa Sentensi',
                'Kanuni za Sarufi',
            ],
            'Environmental Awareness' => [
                'Environment Protection',
                'Resource Conservation',
                'Waste Management',
                'Sustainability',
            ],
            'Living Things' => [
                'Plants',
                'Animals',
                'Human Body',
                'Life Cycles',
            ],
            'Non-Living Things' => [
                'Matter',
                'Energy',
                'Forces',
                'Materials',
            ],
            'Conservation' => [
                'Environment Protection',
                'Resource Conservation',
                'Waste Management',
                'Sustainability',
            ],
            'Scientific Inquiry' => [
                'Observation',
                'Experimentation',
                'Data Collection',
                'Conclusion',
            ],
            'Life Sciences' => [
                'Cells and Organisms',
                'Ecosystems',
                'Genetics',
                'Evolution',
            ],
            'Physical Sciences' => [
                'Matter and Energy',
                'Forces and Motion',
                'Waves and Sound',
                'Light and Electricity',
            ],
            'Earth Sciences' => [
                'Earth Structure',
                'Weather and Climate',
                'Solar System',
                'Natural Resources',
            ],
            'History' => [
                'Historical Events',
                'Historical Figures',
                'Timeline',
                'Historical Sources',
            ],
            'Geography' => [
                'Physical Geography',
                'Human Geography',
                'Maps and Globes',
                'Regions',
            ],
            'Citizenship' => [
                'Rights and Responsibilities',
                'Governance',
                'Democracy',
                'Law and Order',
            ],
            'Culture' => [
                'Cultural Practices',
                'Traditions',
                'Values',
                'Diversity',
            ],
        ];

        if (isset($names[$strandName]) && isset($names[$strandName][$index - 1])) {
            return $names[$strandName][$index - 1];
        }

        return "{$strandName} - Substrand {$index}";
    }

    /**
     * Get learning outcomes for substrand
     */
    protected function getLearningOutcomes(string $strandName, int $index): array
    {
        return [
            "By the end of the substrand, the learner should be able to demonstrate understanding of {$strandName} concepts",
            "Apply knowledge of {$strandName} in practical situations",
            "Develop critical thinking skills related to {$strandName}",
            "Communicate effectively about {$strandName}",
        ];
    }

    /**
     * Get key inquiry questions for substrand
     */
    protected function getKeyInquiryQuestions(string $strandName, int $index): array
    {
        return [
            "What is the importance of {$strandName}?",
            "How can we apply {$strandName} in daily life?",
            "What are the key concepts in {$strandName}?",
            "How does {$strandName} relate to other learning areas?",
        ];
    }

    /**
     * Seed competencies for all substrands
     */
    protected function seedCompetencies(): void
    {
        $substrands = CBCSubstrand::where('is_active', true)->get();

        foreach ($substrands as $substrand) {
            // Create 2-3 competencies per substrand based on substrand type
            $competencyCount = $this->getCompetencyCount($substrand->name);
            
            for ($i = 1; $i <= $competencyCount; $i++) {
                $code = $substrand->code . '.C' . $i;
                $name = $this->getCompetencyName($substrand->name, $i);
                
                Competency::updateOrCreate(
                    [
                        'substrand_id' => $substrand->id,
                        'code' => $code,
                    ],
                    [
                        'name' => $name,
                        'description' => "Competency {$i} for {$substrand->name}",
                        'indicators' => $this->getCompetencyIndicators($substrand->name, $i),
                        'assessment_criteria' => $this->getAssessmentCriteria($substrand->name, $i),
                        'competency_level' => null, // Set to null - can be updated later based on actual enum values
                        'display_order' => $i,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Get competency count based on substrand name
     */
    protected function getCompetencyCount(string $substrandName): int
    {
        // Most substrands have 2-3 competencies
        return rand(2, 3);
    }

    /**
     * Get competency name based on substrand and index
     */
    protected function getCompetencyName(string $substrandName, int $index): string
    {
        // Generate meaningful competency names based on substrand
        $names = [
            'Listening for Comprehension' => [
                'Listen and respond to oral instructions',
                'Comprehend and retell stories',
            ],
            'Speaking Fluently' => [
                'Speak clearly and fluently',
                'Participate in conversations',
            ],
            'Word Recognition' => [
                'Recognize and read words',
                'Use phonics to decode words',
            ],
            'Reading Comprehension' => [
                'Understand and interpret text',
                'Answer questions about text',
            ],
            'Number Recognition' => [
                'Recognize and name numbers',
                'Count objects accurately',
            ],
            'Number Operations' => [
                'Perform basic calculations',
                'Solve number problems',
            ],
        ];

        if (isset($names[$substrandName]) && isset($names[$substrandName][$index - 1])) {
            return $names[$substrandName][$index - 1];
        }

        return "{$substrandName} - Competency {$index}";
    }

    /**
     * Get competency indicators
     */
    protected function getCompetencyIndicators(string $substrandName, int $index): array
    {
        return [
            "Demonstrates understanding of key concepts in {$substrandName}",
            "Applies knowledge in practical situations",
            "Shows critical thinking skills",
            "Communicates effectively",
        ];
    }

    /**
     * Get assessment criteria
     */
    protected function getAssessmentCriteria(string $substrandName, int $index): array
    {
        return [
            "Accuracy in applying concepts",
            "Clarity of communication",
            "Depth of understanding",
            "Practical application",
        ];
    }

    /**
     * Get competency level
     */
    protected function getCompetencyLevel(int $index): string
    {
        $levels = ['Basic', 'Intermediate', 'Advanced'];
        return $levels[($index - 1) % 3];
    }
}

