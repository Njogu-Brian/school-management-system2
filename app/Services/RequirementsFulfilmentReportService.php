<?php

namespace App\Services;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\AcademicYear;
use App\Models\RequirementTemplate;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RequirementsFulfilmentReportService
{
    /**
     * Learner-centric fulfilment: complete / partial / none, with what was brought
     * and what is still expected.
     *
     * @return array{
     *     year:?AcademicYear,
     *     term:?Term,
     *     classrooms:Collection,
     *     streams:Collection,
     *     summary:array{complete:int,partial:int,none:int,learners:int},
     *     complete:list<array<string,mixed>>,
     *     partial:list<array<string,mixed>>,
     *     none:list<array<string,mixed>>
     * }
     */
    public function build(
        ?int $academicYearId,
        ?int $termId,
        ?int $classroomId,
        ?int $streamId,
    ): array {
        $year = $academicYearId
            ? AcademicYear::find($academicYearId)
            : AcademicYear::where('is_active', true)->orderByDesc('id')->first();

        $term = $termId
            ? Term::find($termId)
            : Term::where('is_current', true)->orderByDesc('id')->first();

        $classrooms = Classroom::query()->orderBy('name')->get();
        $streams = $classroomId
            ? Stream::query()->where('classroom_id', $classroomId)->orderBy('name')->get()
            : collect();

        $students = $this->studentsQuery($classroomId, $streamId)->get();
        $classroomIds = $students->pluck('classroom_id')->filter()->unique()->values()->all();

        $templates = $this->templatesFor($year, $term, $classroomIds);
        $requirements = $this->requirementsFor($students->pluck('id')->all(), $year, $term);

        $complete = [];
        $partial = [];
        $none = [];

        foreach ($students as $student) {
            $row = $this->rowForStudent($student, $templates, $requirements, $term);
            if ($row === null) {
                continue;
            }
            if ($row['group'] === 'complete') {
                $complete[] = $row;
            } elseif ($row['group'] === 'partial') {
                $partial[] = $row;
            } else {
                $none[] = $row;
            }
        }

        return [
            'year' => $year,
            'term' => $term,
            'classrooms' => $classrooms,
            'streams' => $streams,
            'summary' => [
                'complete' => count($complete),
                'partial' => count($partial),
                'none' => count($none),
                'learners' => count($complete) + count($partial) + count($none),
            ],
            'complete' => $complete,
            'partial' => $partial,
            'none' => $none,
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function csvRows(array $report): array
    {
        $rows = [[
            'Status',
            'Admission',
            'Learner',
            'Class',
            'Stream',
            'Item',
            'Handling',
            'Expected',
            'Brought',
            'Still expected',
        ]];

        foreach (['complete' => 'Fully brought', 'partial' => 'Partial', 'none' => 'Nothing brought'] as $group => $label) {
            foreach ($report[$group] as $learner) {
                foreach ($learner['items'] as $item) {
                    $rows[] = [
                        $label,
                        (string) $learner['admission_number'],
                        (string) $learner['name'],
                        (string) $learner['class_name'],
                        (string) $learner['stream_name'],
                        (string) $item['name'],
                        (string) $item['handling'],
                        $this->qty($item['expected']).' '.$item['unit'],
                        $this->qty($item['brought']).' '.$item['unit'],
                        $this->qty($item['outstanding']).' '.$item['unit'],
                    ];
                }
            }
        }

        return $rows;
    }

    private function studentsQuery(?int $classroomId, ?int $streamId)
    {
        $query = Student::query()
            ->with(['classroom', 'stream'])
            ->where(function ($q) {
                $q->where('archive', 0)->orWhereNull('archive');
            })
            ->where(function ($q) {
                $q->where('is_alumni', false)->orWhereNull('is_alumni');
            });

        if (Schema::hasColumn('students', 'status')) {
            $query->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            });
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }
        if ($streamId) {
            $query->where('stream_id', $streamId);
        }

        return $query->orderBy('first_name')->orderBy('last_name');
    }

    /**
     * @param  list<int>  $classroomIds
     */
    private function templatesFor(?AcademicYear $year, ?Term $term, array $classroomIds): Collection
    {
        if ($classroomIds === []) {
            return collect();
        }

        return RequirementTemplate::query()
            ->with(['requirementType', 'classrooms'])
            ->where('is_active', true)
            ->where(function ($q) use ($classroomIds) {
                $q->whereIn('classroom_id', $classroomIds)
                    ->orWhereHas('classrooms', fn ($qq) => $qq->whereIn('classrooms.id', $classroomIds))
                    ->orWhereNull('classroom_id');
            })
            ->when($term, fn ($q) => $q->where(function ($qq) use ($term) {
                $qq->where('term_id', $term->id)->orWhereNull('term_id');
            }))
            ->when($year, fn ($q) => $q->where(function ($qq) use ($year) {
                $qq->where('academic_year_id', $year->id)->orWhereNull('academic_year_id');
            }))
            ->get();
    }

    /**
     * @param  list<int>  $studentIds
     */
    private function requirementsFor(array $studentIds, ?AcademicYear $year, ?Term $term): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        return StudentRequirement::query()
            ->whereIn('student_id', $studentIds)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->when($year, fn ($q) => $q->where(function ($qq) use ($year) {
                $qq->where('academic_year_id', $year->id)->orWhereNull('academic_year_id');
            }))
            ->get()
            ->groupBy('student_id');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rowForStudent(Student $student, Collection $templates, Collection $requirements, ?Term $term): ?array
    {
        $allowedTypes = $this->isNewJoiner($student, $term) ? ['new', 'both'] : ['existing', 'both'];
        $applicable = $templates->filter(function (RequirementTemplate $tpl) use ($student, $allowedTypes) {
            if (! in_array($tpl->student_type, $allowedTypes, true)) {
                return false;
            }
            if ($tpl->classroom_id === null) {
                $assignedIds = $tpl->relationLoaded('classrooms')
                    ? $tpl->classrooms->pluck('id')->all()
                    : [];
                if ($assignedIds === []) {
                    return true;
                }

                return in_array((int) $student->classroom_id, $assignedIds, true);
            }

            if ((int) $tpl->classroom_id === (int) $student->classroom_id) {
                return true;
            }

            return $tpl->relationLoaded('classrooms')
                && $tpl->classrooms->contains('id', $student->classroom_id);
        });

        if ($applicable->isEmpty()) {
            return null;
        }

        $existing = $requirements->get($student->id, collect())->keyBy('requirement_template_id');
        $items = [];
        $completeCount = 0;
        $broughtAnything = false;

        foreach ($applicable as $tpl) {
            /** @var StudentRequirement|null $req */
            $req = $existing->get($tpl->id);
            $expected = (float) ($req?->expected_quantity ?? $req?->quantity_required ?? $tpl->quantity_per_student ?? 0);
            $brought = (float) ($req?->quantity_collected ?? 0);
            $outstanding = max(0, $expected - $brought);
            $itemComplete = $expected <= 0 || $brought >= $expected;
            if ($itemComplete) {
                $completeCount++;
            }
            if ($brought > 0) {
                $broughtAnything = true;
            }

            $verify = (bool) $tpl->is_verification_only;
            $items[] = [
                'name' => $tpl->requirementType?->name ?? 'Requirement',
                'brand' => $tpl->brand,
                'unit' => $tpl->unit ?? 'pcs',
                'expected' => $expected,
                'brought' => $brought,
                'outstanding' => $outstanding,
                'status' => $itemComplete ? 'complete' : ($brought > 0 ? 'partial' : 'pending'),
                'handling' => $verify ? 'Verify (learner keeps)' : ($tpl->addsToSchoolInventory() ? 'Collect (school stock)' : 'Record'),
                'is_verification_only' => $verify,
            ];
        }

        $total = count($items);
        if ($completeCount === $total) {
            $group = 'complete';
        } elseif (! $broughtAnything) {
            $group = 'none';
        } else {
            $group = 'partial';
        }

        return [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'name' => $student->full_name,
            'class_name' => $student->classroom?->name ?? '—',
            'stream_name' => $student->stream?->name ?? '—',
            'group' => $group,
            'complete_count' => $completeCount,
            'total_count' => $total,
            'items' => $items,
            'brought_items' => array_values(array_filter($items, fn ($i) => $i['brought'] > 0)),
            'outstanding_items' => array_values(array_filter($items, fn ($i) => $i['outstanding'] > 0)),
        ];
    }

    private function isNewJoiner(Student $student, ?Term $term): bool
    {
        if (! $term) {
            return false;
        }
        if ($student->enrollment_year === null || $student->enrollment_term === null) {
            return false;
        }
        $currentYear = $term->academicYear?->year ?? (int) date('Y');
        $termNumber = $this->termNumber($term);

        return (int) $student->enrollment_year === (int) $currentYear
            && (int) $student->enrollment_term === (int) $termNumber;
    }

    private function termNumber(Term $term): int
    {
        static $cache = [];
        $key = (int) $term->academic_year_id;
        if (! isset($cache[$key])) {
            $cache[$key] = Term::where('academic_year_id', $term->academic_year_id)
                ->orderBy('opening_date')
                ->pluck('id')
                ->all();
        }
        $idx = array_search($term->id, $cache[$key], true);

        return $idx === false ? 1 : ($idx + 1);
    }

    private function qty(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? (string) (int) $value
            : number_format($value, 2);
    }
}
