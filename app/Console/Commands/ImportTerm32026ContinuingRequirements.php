<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\RequirementTemplate;
use App\Models\RequirementTemplateAssignment;
use App\Models\RequirementType;
use App\Models\Term;
use App\Models\Academics\Classroom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportTerm32026ContinuingRequirements extends Command
{
    protected $signature = 'requirements:import-term3-2026-continuing
        {--dry-run : Preview without writing}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Import Term 3 2026 continuing-student requirements per class for teacher mobile collection.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $catalog = require database_path('data/term3_2026_continuing_requirements.php');

        $year = AcademicYear::query()
            ->where('year', 2026)
            ->orWhere('year', '2026')
            ->first();

        if (! $year) {
            $this->error('Academic year 2026 was not found.');

            return self::FAILURE;
        }

        $term = Term::query()
            ->where('academic_year_id', $year->id)
            ->where(function ($q) {
                $q->where('name', 'Term 3')
                    ->orWhere('name', 'TERM 3')
                    ->orWhere('name', 'like', '%Term 3%');
            })
            ->first();

        if (! $term) {
            $this->error('Term 3 for academic year 2026 was not found.');

            return self::FAILURE;
        }

        $this->info("Academic year: {$year->year} (id {$year->id})");
        $this->info("Term: {$term->name} (id {$term->id})");
        $this->info('Student type: existing (continuing)');
        $this->newLine();

        $classrooms = Classroom::query()->orderBy('name')->get(['id', 'name']);
        $resolved = [];
        $missing = [];

        foreach (array_keys($catalog) as $label) {
            $matches = $this->matchClassrooms($label, $classrooms);
            if ($matches->isEmpty()) {
                $missing[] = $label;
            } else {
                $resolved[$label] = $matches;
                $this->line("{$label} → ".$matches->pluck('name')->implode(', '));
            }
        }

        if ($missing !== []) {
            $this->error('No classroom match for: '.implode(', ', $missing));
            $this->comment('Available classrooms: '.$classrooms->pluck('name')->implode(', '));

            return self::FAILURE;
        }

        $rows = [];
        foreach ($catalog as $label => $items) {
            foreach ($resolved[$label] as $classroom) {
                foreach ($items as $item) {
                    $rows[] = [
                        'class_label' => $label,
                        'classroom' => $classroom,
                        'item' => $item,
                    ];
                }
            }
        }

        $this->newLine();
        $this->info('Preview: '.count($rows).' class-item rows across '.count($catalog).' class groups.');

        if ($dryRun) {
            foreach ($resolved as $label => $matches) {
                $this->newLine();
                $this->comment($label.' ('.$matches->pluck('name')->implode(', ').')');
                foreach ($catalog[$label] as $item) {
                    $this->line(sprintf(
                        '  %-48s qty %s %-8s %s',
                        $item['name'],
                        $item['quantity'],
                        $item['unit'],
                        $item['brand'] ?? ''
                    ));
                }
            }
            $this->warn('Dry run — nothing was written.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Create/update these requirement items for continuing students?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $createdTypes = 0;
        $createdTemplates = 0;
        $updatedTemplates = 0;
        $createdAssignments = 0;
        $updatedAssignments = 0;
        $hasAssignments = Schema::hasTable('requirement_template_assignments');

        DB::transaction(function () use (
            $rows,
            $year,
            $term,
            $hasAssignments,
            &$createdTypes,
            &$createdTemplates,
            &$updatedTemplates,
            &$createdAssignments,
            &$updatedAssignments
        ) {
            foreach ($rows as $row) {
                $item = $row['item'];
                $classroom = $row['classroom'];
                $category = $this->mapCategory($item['category']);

                $type = RequirementType::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($item['name'])])
                    ->first();

                if (! $type) {
                    $type = RequirementType::create([
                        'name' => $item['name'],
                        'category' => $category,
                        'description' => 'Term 3 2026 continuing requirement',
                        'is_active' => true,
                    ]);
                    $createdTypes++;
                } else {
                    $type->fill([
                        'category' => $category,
                        'is_active' => true,
                    ])->save();
                }

                $payload = [
                    'brand' => $item['brand'],
                    'quantity_per_student' => $item['quantity'],
                    'unit' => $item['unit'],
                    'student_type' => 'existing',
                    'leave_with_teacher' => (bool) $item['leave_with_teacher'],
                    'custody_type' => $item['leave_with_teacher'] ? 'school_custody' : 'parent_custody',
                    'is_verification_only' => (bool) $item['is_verification_only'],
                    'is_active' => true,
                    'notes' => $this->notesFor($item),
                ];

                $template = RequirementTemplate::query()
                    ->where('requirement_type_id', $type->id)
                    ->where('classroom_id', $classroom->id)
                    ->where('academic_year_id', $year->id)
                    ->where('term_id', $term->id)
                    ->whereIn('student_type', ['existing', 'both'])
                    ->orderByRaw("CASE WHEN student_type = 'existing' THEN 0 ELSE 1 END")
                    ->first();

                if ($template) {
                    $template->update($payload);
                    $updatedTemplates++;
                } else {
                    $template = RequirementTemplate::create(array_merge($payload, [
                        'requirement_type_id' => $type->id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $year->id,
                        'term_id' => $term->id,
                    ]));
                    $createdTemplates++;
                }

                if ($hasAssignments) {
                    $assignment = RequirementTemplateAssignment::query()
                        ->where('requirement_template_id', $template->id)
                        ->where('academic_year_id', $year->id)
                        ->where('term_id', $term->id)
                        ->where('classroom_id', $classroom->id)
                        ->whereIn('student_type', ['existing', 'both'])
                        ->orderByRaw("CASE WHEN student_type = 'existing' THEN 0 ELSE 1 END")
                        ->first();

                    $assignmentPayload = [
                        'requirement_template_id' => $template->id,
                        'academic_year_id' => $year->id,
                        'term_id' => $term->id,
                        'classroom_id' => $classroom->id,
                        'student_type' => 'existing',
                        'brand' => $item['brand'],
                        'quantity_per_student' => $item['quantity'],
                        'unit' => $item['unit'],
                        'notes' => $this->notesFor($item),
                        'leave_with_teacher' => (bool) $item['leave_with_teacher'],
                        'is_verification_only' => (bool) $item['is_verification_only'],
                        'is_active' => true,
                    ];

                    if ($assignment) {
                        $assignment->update($assignmentPayload);
                        $updatedAssignments++;
                    } else {
                        RequirementTemplateAssignment::create($assignmentPayload);
                        $createdAssignments++;
                    }
                }
            }
        });

        $this->newLine();
        $this->info("Requirement types created: {$createdTypes}");
        $this->info("Templates created: {$createdTemplates}, updated: {$updatedTemplates}");
        if ($hasAssignments) {
            $this->info("Assignments created: {$createdAssignments}, updated: {$updatedAssignments}");
        }
        $this->info('Teachers can now receive these items on the mobile app for continuing students in Term 3 2026.');

        return self::SUCCESS;
    }

    private function notesFor(array $item): string
    {
        $bits = ['Imported from Term 3 2026 requirements PDF'];
        if (! empty($item['notes'])) {
            $bits[] = $item['notes'];
        }

        return implode('. ', $bits);
    }

    private function mapCategory(string $category): string
    {
        $presets = array_keys(RequirementType::presetCategories());
        $aliases = [
            'toiletries' => 'toiletries',
            'stationery' => 'stationery',
            'books' => 'books',
            'other' => 'other',
        ];
        $mapped = $aliases[$category] ?? $category;

        return in_array($mapped, $presets, true) ? $mapped : 'other';
    }

    private function matchClassrooms(string $label, $classrooms)
    {
        $target = $this->normalize($label);
        $targets = [$target];
        foreach ($this->labelAliases($label) as $alias) {
            $targets[] = $this->normalize($alias);
        }
        $targets = array_values(array_unique($targets));

        $exact = $classrooms->filter(fn ($c) => in_array($this->normalize((string) $c->name), $targets, true));
        if ($exact->isNotEmpty()) {
            return $exact->values();
        }

        return $classrooms->filter(function ($c) use ($targets) {
            $norm = $this->normalize((string) $c->name);
            foreach ($targets as $target) {
                if ($norm === $target) {
                    return true;
                }
                if (! str_starts_with($norm, $target)) {
                    continue;
                }
                $next = substr($norm, strlen($target), 1);
                if ($next !== '' && ! ctype_digit($next)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    private function labelAliases(string $label): array
    {
        return match (strtoupper(trim($label))) {
            'PP1' => ['PP1', 'PP 1', 'Pre Primary 1', 'Pre-Primary 1'],
            'PP2' => ['PP2', 'PP 2', 'Pre Primary 2', 'Pre-Primary 2'],
            'FOUNDATION' => ['Foundation', 'Foundation Class'],
            default => [$label],
        };
    }

    private function normalize(string $name): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $name));
    }
}
