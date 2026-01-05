<?php

namespace Database\Seeders;

use App\Models\RequirementType;
use App\Models\RequirementTemplate;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Requirements2026Seeder extends Seeder
{
    /**
     * Raw data from REQUIREMENTS 2026.pdf
     */
    private array $requirementsData = [
        'PP1' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Wet Wipes', 'quantity' => '1', 'brand' => 'Softcare'],
            ['item' => 'A4 Photocopy Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Jumbo Crayons', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'Pencils', 'quantity' => '1 Packet', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Masking Tape (1 inch)', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Erasers (white)', 'quantity' => '12', 'brand' => 'Nataraj'],
            ['item' => 'Sharpeners', 'quantity' => '12', 'brand' => 'Nataraj'],
            ['item' => 'Manila Paper', 'quantity' => '2', 'brand' => '—'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => '—'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Story Books (Peter & Jane 1a, 1b, 2a, 2b)', 'quantity' => '', 'brand' => 'Ladybird Series'],
            ['item' => 'Sound and read book 1', 'quantity' => '1', 'brand' => ''],
            ['item' => 'Numberwork Textbook pp1', 'quantity' => '1', 'brand' => 'Queenex'],
            ['item' => 'English Language Textbook pp1', 'quantity' => '1', 'brand' => 'Queenex'],
            ['item' => 'Environmental Textbook pp1', 'quantity' => '1', 'brand' => 'Queenex'],
            ['item' => 'Religious Textbook pp1', 'quantity' => '', 'brand' => 'Queenex'],
            ['item' => 'Creative Textbook pp1', 'quantity' => '1', 'brand' => 'Queenex'],
        ],
        'PP2' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Wet Wipes', 'quantity' => '1', 'brand' => 'Softcare'],
            ['item' => 'A4 Photocopy Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Jumbo Crayons', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'Pencils', 'quantity' => '1 Packet', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Masking Tape (1 inch)', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Erasers (white)', 'quantity' => '12', 'brand' => 'Nataraj'],
            ['item' => 'Sharpeners', 'quantity' => '12', 'brand' => 'Nataraj'],
            ['item' => 'Manila Paper', 'quantity' => '2', 'brand' => '—'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => '—'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Story Books (Peter & Jane 3a, 3b, 4a, 4b)', 'quantity' => '', 'brand' => 'Ladybird Series'],
            ['item' => 'Sound and read book 2', 'quantity' => '1', 'brand' => ''],
            ['item' => 'Numberwork Textbook pp2', 'quantity' => '1', 'brand' => 'Queenex'],
            ['item' => 'English Language Textbook pp2', 'quantity' => '1', 'brand' => 'Queenex'],
            ['item' => 'Environmental Textbook pp2', 'quantity' => '1', 'brand' => 'Queenex'],
            ['item' => 'Religious Textbook pp2', 'quantity' => '1', 'brand' => 'Queenex'],
            ['item' => 'Creative Textbook pp2', 'quantity' => '1', 'brand' => 'Queenex'],
        ],
        'Grade 1' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Pencil Colours', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '2', 'brand' => 'pcs'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => 'pcs'],
            ['item' => 'pencils', 'quantity' => '1 pkt', 'brand' => 'Nataraj, pelikan'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'White Erasers', 'quantity' => '6', 'brand' => 'Nataraj'],
            ['item' => 'Masking Tape 1 inch', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Spring File', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Story Books (Peter & Jane 3a 3b 4a 4b 5a, 5b,)', 'quantity' => '', 'brand' => 'Ladybird Series'],
            ['item' => 'Mathematical Activities Workbook 1', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'New Progressive English Workbook 1', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Kiswahili Dadisi Workbook 1', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Environmental Workbook 1', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'C.R.E Workbook 1', 'quantity' => '1', 'brand' => 'Oxford'],
        ],
        'Grade 2' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Pencil Colours', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '1 Packet', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Pencils porch with pencils, rubber, sharpeners,', 'quantity' => '1 inch', 'brand' => '—'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'White Erasers', 'quantity' => '12', 'brand' => 'Nataraj'],
            ['item' => 'Masking Tape half inch', 'quantity' => '2', 'brand' => '—'],
            ['item' => 'Exercise books -', 'quantity' => '2', 'brand' => '—'],
            ['item' => 'Ruled books - A5 120 pages', 'quantity' => '8', 'brand' => ''],
            ['item' => 'Squired books - A5 120 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Spring File', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Story Books (Peter & Jane 6a, 6b, 7a, 7b, 8a, 8b)', 'quantity' => '', 'brand' => 'Ladybird Series'],
            ['item' => 'Mathematical Activities Workbook 2', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'New Progressive English Workbook 2', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Kiswahili Dadisi Workbook 2', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Environmental Workbook 2', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'C.R.E Workbook 2', 'quantity' => '1', 'brand' => 'Oxford'],
        ],
        'Grade 3' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Pencil Colours', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '1 Packet', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Pencils porch with pencils, rubber, sharpeners,', 'quantity' => '1 inch', 'brand' => '—'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'White Erasers', 'quantity' => '6', 'brand' => 'Nataraj'],
            ['item' => 'Masking Tape 1 inch', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Exercise books -', 'quantity' => '2', 'brand' => '—'],
            ['item' => 'Ruled books - A5 120 pages', 'quantity' => '8', 'brand' => ''],
            ['item' => 'Squired books - A5 120 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Spring File', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Story Books (Peter & Jane 9a, 9b, 10a, 10b, 11a, 11b)', 'quantity' => '', 'brand' => 'Ladybird Series'],
            ['item' => 'Mathematical Activities Workbook 3', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'New Progressive English Workbook 3', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Kiswahili Dadisi Workbook 3', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Environmental Workbook 3', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'C.R.E Workbook 3', 'quantity' => '1', 'brand' => 'Oxford'],
        ],
        'Grade 4' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Pencil Colours', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '3', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Luminous Paper', 'quantity' => '3', 'brand' => ''],
            ['item' => 'Pencils porch with pencils, rubber, sharpeners,', 'quantity' => '', 'brand' => '—'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Masking Tape 1 inch', 'quantity' => '1', 'brand' => '—'],
            ['item' => '30cm ruler, a pair of scissors', 'quantity' => '1', 'brand' => ''],
            ['item' => 'Exercise books -', 'quantity' => '', 'brand' => '—'],
            ['item' => 'Ruled books - A5 120 pages', 'quantity' => '8', 'brand' => ''],
            ['item' => 'Squired books - A5 120 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'A4 ruled books 80 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Spring File', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Story Books (Peter & Jane 12a, 12b)', 'quantity' => '', 'brand' => 'Ladybird Series'],
            ['item' => 'Moran Atlas', 'quantity' => '', 'brand' => ''],
            ['item' => 'Pace setters - both English and Kiswahili', 'quantity' => '', 'brand' => ''],
            ['item' => 'Primary Kamusi, Learners dictionary, Good news bible', 'quantity' => '', 'brand' => ''],
            ['item' => 'Mathematical Activities Workbook 4', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'New Progressive English Workbook 4', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Kiswahili Dadisi Workbook 4', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Environmental Workbook 4', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'C.R.E Workbook 4', 'quantity' => '1', 'brand' => 'Oxford'],
        ],
        'Grade 5' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Pencil Colours', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '1 Packet', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Pencils porch with pencils, rubber, sharpeners,', 'quantity' => '1 inch', 'brand' => '—'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'White Erasers', 'quantity' => '12', 'brand' => 'Nataraj'],
            ['item' => 'Masking Tape half inch', 'quantity' => '2', 'brand' => '—'],
            ['item' => '30cm ruler, a pair of scissors', 'quantity' => '', 'brand' => ''],
            ['item' => 'Exercise books -', 'quantity' => '', 'brand' => '—'],
            ['item' => 'Ruled books - A5 200 pages', 'quantity' => '7', 'brand' => ''],
            ['item' => 'Squired books - A5 200 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Spring File', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'Reading Diary both Kiswahili and English', 'quantity' => '', 'brand' => ''],
            ['item' => 'Pace setters both Kiswahili and English', 'quantity' => '', 'brand' => ''],
            ['item' => 'Comprehensive Atlas', 'quantity' => '', 'brand' => ''],
            ['item' => 'Primary Kamusi, dictionary, Good news bible', 'quantity' => '', 'brand' => ''],
            ['item' => 'Mathematical Activities Workbook 5', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'New Progressive English Workbook 5', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Kiswahili Dadisi Workbook 5', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Environmental Workbook 5', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'C.R.E Workbook 5', 'quantity' => '1', 'brand' => 'Oxford'],
        ],
        'Grade 6' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Pencil Colours', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '1 Packet', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Pencils porch with pencils, rubber, sharpeners,', 'quantity' => '1 inch', 'brand' => '—'],
            ['item' => 'Scrap Book', 'quantity' => '1', 'brand' => '—'],
            ['item' => 'White Erasers', 'quantity' => '12', 'brand' => 'Nataraj'],
            ['item' => 'Masking Tape half inch', 'quantity' => '2', 'brand' => '—'],
            ['item' => '30cm ruler, a pair of scissors', 'quantity' => '', 'brand' => ''],
            ['item' => 'Exercise ex books -', 'quantity' => '', 'brand' => '—'],
            ['item' => 'Ruled ex books - A5 200 pages', 'quantity' => '7', 'brand' => ''],
            ['item' => 'Squired books - A5 200 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'A4 ruled ex books', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Reading Diary both Kiswahili and English', 'quantity' => '', 'brand' => ''],
            ['item' => 'Pace setters both Kiswahili and English', 'quantity' => '', 'brand' => ''],
            ['item' => 'Comprehensive Atlas', 'quantity' => '', 'brand' => ''],
            ['item' => 'Primary Kamusi, dictionary, Good news bible', 'quantity' => '', 'brand' => ''],
            ['item' => 'Mathematical Activities Workbook 6', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'New Progressive English Workbook 6', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Kiswahili Dadisi Workbook 6', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'Environmental Workbook 6', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'C.R.E Workbook 6', 'quantity' => '1', 'brand' => 'Oxford'],
        ],
        'Grade 7' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'Pencil Colours', 'quantity' => '1 Packet', 'brand' => '—'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '2 Packet', 'brand' => 'Nataraj, Pelican'],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Pencils porch with pencils, rubber, sharpeners,', 'quantity' => '1 inch', 'brand' => '—'],
            ['item' => 'Masking Tape half inch', 'quantity' => '2', 'brand' => '—'],
            ['item' => '30cm ruler, a pair of scissors', 'quantity' => '', 'brand' => ''],
            ['item' => 'Ruled ex books - A4 200 pages', 'quantity' => '10', 'brand' => ''],
            ['item' => 'Squired books - A4 200 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'A4 ruled ex books', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Reading Diary both Kiswahili and English', 'quantity' => '', 'brand' => ''],
            ['item' => 'Primary Kamusi, dictionary, good news bible', 'quantity' => '', 'brand' => ''],
            ['item' => 'Comprehensive Atlas', 'quantity' => '', 'brand' => ''],
            ['item' => 'Bridge without river', 'quantity' => '', 'brand' => ''],
            ['item' => 'Melodies of Africa', 'quantity' => '', 'brand' => ''],
            ['item' => 'Daughter of Nature', 'quantity' => '', 'brand' => ''],
            ['item' => 'Mshale wa Matumaini', 'quantity' => '', 'brand' => 'Kiswahili'],
            ['item' => 'Mji wa Matarajio', 'quantity' => '', 'brand' => 'Kiswahili'],
            ['item' => 'Uteuzi wa Chalo', 'quantity' => '', 'brand' => 'Kiswahili'],
            ['item' => 'Shara yangu ya Kusuma ( stomoja) level', 'quantity' => '', 'brand' => ''],
            ['item' => 'TARGETER ENCYCLOPAEDIA VOLUME 1', 'quantity' => '1', 'brand' => ''],
            ['item' => 'TARGETER ENCYCLOPAEDIA VOLUME 2', 'quantity' => '1', 'brand' => ''],
        ],
        'Grade 8' => [
            ['item' => 'Tissue Rolls', 'quantity' => '3', 'brand' => 'Hannan, Velvex, Bella, Toilex, Neptune'],
            ['item' => 'JK Ream Paper', 'quantity' => '1', 'brand' => 'JK'],
            ['item' => 'Manila Paper', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Luminous Paper', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Pencils porch with pencils, rubber, sharpeners,', 'quantity' => '1 inch', 'brand' => '—'],
            ['item' => 'Masking Tape half inch', 'quantity' => '2', 'brand' => '—'],
            ['item' => '30cm ruler, a pair of scissors', 'quantity' => '', 'brand' => ''],
            ['item' => 'Ruled ex books - A4 200 pages', 'quantity' => '10', 'brand' => ''],
            ['item' => 'Squired books - A4 200 pages', 'quantity' => '2', 'brand' => ''],
            ['item' => 'Reading Diary both Kiswahili and English', 'quantity' => '', 'brand' => ''],
            ['item' => 'Mathematical table', 'quantity' => '1', 'brand' => ''],
            ['item' => 'Scientific Calculator', 'quantity' => '1', 'brand' => ''],
            ['item' => 'Primary Kamusi, dictionary, good news bible', 'quantity' => '', 'brand' => ''],
            ['item' => 'Comprehensive Atlas', 'quantity' => '', 'brand' => ''],
            ['item' => 'Bridge without river', 'quantity' => '', 'brand' => ''],
            ['item' => 'Melodies of Africa', 'quantity' => '', 'brand' => ''],
            ['item' => 'Daughter of Nature', 'quantity' => '', 'brand' => ''],
            ['item' => 'Mshale wa Matumaini', 'quantity' => '', 'brand' => 'Kiswahili'],
            ['item' => 'Mji wa Matarajio', 'quantity' => '', 'brand' => 'Kiswahili'],
            ['item' => 'Uteuzi wa Chalo', 'quantity' => '', 'brand' => 'Kiswahili'],
            ['item' => 'Wema Hauosi', 'quantity' => '', 'brand' => ''],
            ['item' => 'TARGETER ENCYCLOPAEDIA VOLUME 1', 'quantity' => '1', 'brand' => 'Oxford'],
            ['item' => 'TARGETER ENCYCLOPAEDIA VOLUME 2', 'quantity' => '1', 'brand' => 'Oxford'],
        ],
    ];

    private ?AcademicYear $academicYear = null;
    private ?Term $term = null;
    private array $requirementTypeCache = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== Requirements 2026 Seeder ===');
        $this->command->newLine();

        // Get academic year and term
        $this->academicYear = AcademicYear::where('year', '2026')->first();
        $this->term = Term::where('academic_year_id', $this->academicYear?->id)
            ->where('name', 'Term 1')
            ->first();

        if (!$this->academicYear || !$this->term) {
            $this->command->error('Academic Year 2026 or Term 1 not found!');
            return;
        }

        $this->command->info("Academic Year: {$this->academicYear->year}");
        $this->command->info("Term: {$this->term->name}");
        $this->command->newLine();

        // Process and preview
        $preview = $this->processRequirements();

        // Display preview
        $this->displayPreview($preview);

        // Ask for confirmation
        if (!$this->command->confirm('Do you want to proceed with importing these requirements?', true)) {
            $this->command->info('Import cancelled.');
            return;
        }

        // Import
        $this->importRequirements($preview);

        $this->command->info('Import completed successfully!');
    }

    /**
     * Process requirements and create preview data
     */
    private function processRequirements(): array
    {
        $preview = [];

        foreach ($this->requirementsData as $className => $items) {
            $classroom = Classroom::where('name', $className)->first();

            if (!$classroom) {
                $this->command->warn("Classroom '{$className}' not found. Skipping...");
                continue;
            }

            $preview[$className] = [
                'classroom' => $classroom,
                'items' => [],
            ];

            foreach ($items as $itemData) {
                $processedItems = $this->processItem($itemData, $className);
                foreach ($processedItems as $processed) {
                    if ($processed) {
                        $preview[$className]['items'][] = $processed;
                    }
                }
            }
        }

        return $preview;
    }

    /**
     * Process a single item - may return multiple items for storybooks
     */
    private function processItem(array $itemData, string $className): array
    {
        $itemName = trim($itemData['item']);
        $quantityStr = trim($itemData['quantity'] ?? '');
        $brand = trim($itemData['brand'] ?? '');

        // Handle Ladybird storybooks - split into individual books
        if (preg_match('/story\s*book/i', strtolower($itemName)) && 
            preg_match('/peter\s*&?\s*jane/i', strtolower($itemName)) &&
            preg_match('/ladybird/i', strtolower($brand))) {
            return $this->processLadybirdStorybooks($itemName, $brand, $className);
        }

        // Handle Oxford workbooks - use workbook name as requirement name
        if (preg_match('/workbook/i', strtolower($itemName)) && 
            preg_match('/oxford/i', strtolower($brand))) {
            return $this->processOxfordWorkbook($itemName, $quantityStr, $className);
        }

        // Handle Pencil Colours specifically
        if (preg_match('/pencil\s*colour/i', strtolower($itemName))) {
            return $this->processPencilColours($itemName, $quantityStr, $brand);
        }

        // Parse quantity and unit
        $parsed = $this->parseQuantity($quantityStr);
        $quantity = $parsed['quantity'];
        $unit = $parsed['unit'];

        // Map to requirement type
        $requirementType = $this->mapToRequirementType($itemName, $itemName);

        if (!$requirementType) {
            $this->command->warn("Could not map '{$itemName}' to a requirement type. Skipping...");
            return [];
        }

        // Clean brand (remove '—' and empty values, handle pcs)
        if ($brand === '—' || $brand === '' || strtolower($brand) === 'pcs') {
            $brand = null;
        }

        // Handle Manila and Luminous paper - ensure quantities are set
        if (preg_match('/manila\s*paper/i', strtolower($itemName))) {
            if (preg_match('/(\d+)\s*packet/i', $quantityStr, $matches)) {
                $quantity = (float)$matches[1];
                $unit = 'packet';
            } elseif (preg_match('/(\d+)/', $quantityStr, $matches)) {
                $quantity = (float)$matches[1];
                $unit = 'piece';
            }
            // If no quantity specified, default to 2
            if ($quantity == 1 && $unit == 'piece' && empty($quantityStr)) {
                $quantity = 2;
            }
        }

        if (preg_match('/luminous\s*paper/i', strtolower($itemName))) {
            if (preg_match('/(\d+)/', $quantityStr, $matches)) {
                $quantity = (float)$matches[1];
                $unit = 'piece';
            } elseif (empty($quantityStr)) {
                // Default to 2 if not specified
                $quantity = 2;
                $unit = 'piece';
            }
        }

        return [[
            'item_name' => $itemName,
            'requirement_type' => $requirementType,
            'quantity' => $quantity,
            'unit' => $unit,
            'brand' => $brand,
        ]];
    }

    /**
     * Process Ladybird storybooks - split into individual books
     */
    private function processLadybirdStorybooks(string $itemName, string $brand, string $className): array
    {
        $items = [];
        
        // Extract book numbers (e.g., "6a, 6b, 7a, 7b, 8a, 8b")
        if (preg_match_all('/(\d+[ab])/i', $itemName, $matches)) {
            $bookNumbers = $matches[1];
            
            foreach ($bookNumbers as $bookNum) {
                $bookName = "Ladybird Storybook {$bookNum}";
                $requirementType = $this->getOrCreateRequirementType('Storybooks', 'books');
                
                $items[] = [
                    'item_name' => $bookName,
                    'requirement_type' => $requirementType,
                    'quantity' => 1,
                    'unit' => 'book',
                    'brand' => 'Ladybird',
                ];
            }
        }
        
        return $items;
    }

    /**
     * Process Oxford workbooks
     */
    private function processOxfordWorkbook(string $itemName, string $quantityStr, string $className): array
    {
        $parsed = $this->parseQuantity($quantityStr);
        $quantity = $parsed['quantity'];
        $unit = $parsed['unit'];
        
        // Use the workbook name as the requirement type name
        $requirementType = $this->getOrCreateRequirementType($itemName, 'books');
        
        return [[
            'item_name' => $itemName,
            'requirement_type' => $requirementType,
            'quantity' => $quantity,
            'unit' => $unit,
            'brand' => 'Oxford',
        ]];
    }

    /**
     * Process Pencil Colours
     */
    private function processPencilColours(string $itemName, string $quantityStr, string $brand): array
    {
        $parsed = $this->parseQuantity($quantityStr);
        $quantity = $parsed['quantity'];
        $unit = $parsed['unit'];
        
        // Create requirement type with exact name "Pencil Colours"
        $requirementType = $this->getOrCreateRequirementType('Pencil Colours', 'stationery');
        
        // Brand should be "Any brand" if not specified or if it's "—"
        $finalBrand = ($brand === '—' || $brand === '' || strtolower($brand) === 'pcs') ? 'Any brand' : $brand;
        
        return [[
            'item_name' => 'Pencil Colours',
            'requirement_type' => $requirementType,
            'quantity' => $quantity,
            'unit' => $unit,
            'brand' => $finalBrand,
        ]];
    }

    /**
     * Parse quantity string to extract quantity and unit
     */
    private function parseQuantity(string $quantityStr): array
    {
        $quantityStr = trim($quantityStr);

        // If empty, default to 1
        if ($quantityStr === '') {
            return ['quantity' => 1, 'unit' => 'piece'];
        }

        // Check for common patterns
        if (preg_match('/(\d+(?:\.\d+)?)\s*(packet|pkt|pack|roll|ream|piece|pcs?|book|inch|cm|pair)/i', $quantityStr, $matches)) {
            $qty = (float) $matches[1];
            $unit = strtolower($matches[2]);

            // Normalize units
            $unitMap = [
                'pkt' => 'packet',
                'pack' => 'packet',
                'pcs' => 'piece',
                'pc' => 'piece',
            ];

            $unit = $unitMap[$unit] ?? $unit;

            return ['quantity' => $qty, 'unit' => $unit];
        }

        // If just a number, assume piece
        if (preg_match('/^(\d+(?:\.\d+)?)$/', $quantityStr, $matches)) {
            return ['quantity' => (float) $matches[1], 'unit' => 'piece'];
        }

        // Default
        return ['quantity' => 1, 'unit' => 'piece'];
    }

    /**
     * Map item name to requirement type
     */
    private function mapToRequirementType(string $itemName, ?string $typeName = null): ?RequirementType
    {
        $itemLower = strtolower($itemName);
        $useName = $typeName ?? $itemName;

        // Toiletries - Tissue Rolls (not Tissue Paper)
        if (preg_match('/tissue\s*roll/i', $itemLower)) {
            return $this->getOrCreateRequirementType('Tissue Rolls', 'toiletries');
        }

        if (preg_match('/wet\s*wip/i', $itemLower)) {
            return $this->getOrCreateRequirementType('Wet Wipes', 'toiletries');
        }

        // Stationery
        if (preg_match('/pencil/i', $itemLower) ||
            preg_match('/eraser/i', $itemLower) ||
            preg_match('/sharpener/i', $itemLower) ||
            preg_match('/manila\s*paper/i', $itemLower) ||
            preg_match('/luminous\s*paper/i', $itemLower) ||
            preg_match('/masking\s*tape/i', $itemLower) ||
            preg_match('/ruler/i', $itemLower) ||
            preg_match('/scissors/i', $itemLower) ||
            preg_match('/crayon/i', $itemLower) ||
            preg_match('/exercise\s*book/i', $itemLower) ||
            preg_match('/ruled\s*book/i', $itemLower) ||
            preg_match('/squired\s*book/i', $itemLower) ||
            preg_match('/scrap\s*book/i', $itemLower) ||
            preg_match('/spring\s*file/i', $itemLower) ||
            preg_match('/ream\s*paper/i', $itemLower) ||
            preg_match('/photocopy/i', $itemLower)) {
            // If it's Pencil Colours, use that specific name
            if (preg_match('/pencil\s*colour/i', $itemLower)) {
                return $this->getOrCreateRequirementType('Pencil Colours', 'stationery');
            }
            return $this->getOrCreateRequirementType('Stationery', 'stationery');
        }

        // Storybooks
        if (preg_match('/story\s*book/i', $itemLower) ||
            preg_match('/peter\s*&?\s*jane/i', $itemLower) ||
            preg_match('/ladybird/i', $itemLower) ||
            preg_match('/pace\s*setter/i', $itemLower) ||
            preg_match('/bridge\s*without\s*river/i', $itemLower) ||
            preg_match('/melodies\s*of\s*africa/i', $itemLower) ||
            preg_match('/daughter\s*of\s*nature/i', $itemLower) ||
            preg_match('/mshale\s*wa\s*matumaini/i', $itemLower) ||
            preg_match('/mji\s*wa\s*matarajio/i', $itemLower) ||
            preg_match('/uteuzi\s*wa\s*chalo/i', $itemLower) ||
            preg_match('/wema\s*hauosi/i', $itemLower) ||
            preg_match('/shara\s*yangu/i', $itemLower)) {
            return $this->getOrCreateRequirementType('Storybooks', 'books');
        }

        // Textbooks and Others
        if (preg_match('/textbook/i', $itemLower) ||
            preg_match('/workbook/i', $itemLower) ||
            preg_match('/dictionary/i', $itemLower) ||
            preg_match('/bible/i', $itemLower) ||
            preg_match('/kamusi/i', $itemLower) ||
            preg_match('/atlas/i', $itemLower) ||
            preg_match('/encyclopaedia/i', $itemLower) ||
            preg_match('/encyclopedia/i', $itemLower) ||
            preg_match('/targeter/i', $itemLower) ||
            preg_match('/reading\s*diary/i', $itemLower) ||
            preg_match('/sound\s*and\s*read/i', $itemLower) ||
            preg_match('/mathematical\s*table/i', $itemLower) ||
            preg_match('/calculator/i', $itemLower)) {
            // For Oxford workbooks, use the workbook name itself as the requirement type
            if (preg_match('/oxford/i', strtolower($useName)) || preg_match('/workbook/i', $itemLower)) {
                return $this->getOrCreateRequirementType($useName, 'books');
            }
            return $this->getOrCreateRequirementType('Textbooks and Others', 'books');
        }

        return null;
    }

    /**
     * Get or create requirement type
     */
    private function getOrCreateRequirementType(string $name, string $category): RequirementType
    {
        $cacheKey = $name . '_' . $category;

        if (isset($this->requirementTypeCache[$cacheKey])) {
            return $this->requirementTypeCache[$cacheKey];
        }

        $type = RequirementType::firstOrCreate(
            ['name' => $name],
            [
                'category' => $category,
                'description' => "Requirement type for {$name}",
                'is_active' => true,
            ]
        );

        $this->requirementTypeCache[$cacheKey] = $type;

        return $type;
    }

    /**
     * Display preview table per class
     */
    private function displayPreview(array $preview): void
    {
        $this->command->info('=== PREVIEW: Requirements by Class ===');
        $this->command->newLine();

        foreach ($preview as $className => $classData) {
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->command->info("CLASS: {$className}");
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $items = $classData['items'];
            if (empty($items)) {
                $this->command->warn("  No items to import for this class.");
                $this->command->newLine();
                continue;
            }

            // Table header
            $this->command->line(sprintf(
                "%-50s %-25s %10s %10s %-20s",
                'Item Name',
                'Requirement Type',
                'Quantity',
                'Unit',
                'Brand'
            ));
            $this->command->line(str_repeat('-', 115));

            // Table rows
            foreach ($items as $item) {
                $this->command->line(sprintf(
                    "%-50s %-25s %10s %10s %-20s",
                    Str::limit($item['item_name'], 48),
                    Str::limit($item['requirement_type']->name, 23),
                    $item['quantity'],
                    $item['unit'],
                    Str::limit($item['brand'] ?? 'N/A', 18)
                ));
            }

            $this->command->info("Total items: " . count($items));
            $this->command->newLine();
        }

        // Summary
        $totalItems = array_sum(array_map(fn($c) => count($c['items']), $preview));
        $totalClasses = count($preview);
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("SUMMARY: {$totalClasses} classes, {$totalItems} total items");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->newLine();
    }

    /**
     * Import requirements to database
     */
    private function importRequirements(array $preview): void
    {
        $this->command->info('Importing requirements...');
        $this->command->newLine();

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($preview, &$imported, &$skipped) {
            foreach ($preview as $className => $classData) {
                $classroom = $classData['classroom'];

                foreach ($classData['items'] as $item) {
                    try {
                        // Check if a similar template already exists (same type, class, year, term, and similar item)
                        $existing = RequirementTemplate::where('requirement_type_id', $item['requirement_type']->id)
                            ->where('classroom_id', $classroom->id)
                            ->where('academic_year_id', $this->academicYear->id)
                            ->where('term_id', $this->term->id)
                            ->where(function($q) use ($item) {
                                $q->where('notes', 'like', "%{$item['item_name']}%")
                                  ->orWhere('brand', $item['brand']);
                            })
                            ->first();

                        if ($existing) {
                            // Update existing
                            $existing->update([
                                'brand' => $item['brand'],
                                'quantity_per_student' => $item['quantity'],
                                'unit' => $item['unit'],
                                'is_active' => true,
                                'notes' => "Imported from Requirements 2026 PDF - {$item['item_name']}",
                            ]);
                        } else {
                            // Create new
                            RequirementTemplate::create([
                                'requirement_type_id' => $item['requirement_type']->id,
                                'classroom_id' => $classroom->id,
                                'academic_year_id' => $this->academicYear->id,
                                'term_id' => $this->term->id,
                                'brand' => $item['brand'],
                                'quantity_per_student' => $item['quantity'],
                                'unit' => $item['unit'],
                                'student_type' => 'both',
                                'leave_with_teacher' => false,
                                'is_verification_only' => false,
                                'is_active' => true,
                                'notes' => "Imported from Requirements 2026 PDF - {$item['item_name']}",
                            ]);
                        }

                        $imported++;
                    } catch (\Exception $e) {
                        $this->command->error("Error importing {$item['item_name']} for {$className}: " . $e->getMessage());
                        $skipped++;
                    }
                }
            }
        });

        $this->command->info("Import complete: {$imported} items imported, {$skipped} skipped.");
    }
}