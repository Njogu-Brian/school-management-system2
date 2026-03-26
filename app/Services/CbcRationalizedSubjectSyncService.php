<?php

namespace App\Services;

use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kenya CBC subjects as one row per code (e.g. single ENG for all levels).
 * Assignment to classes is per classroom level_type; lesson hints live in meta.
 * Classrooms with streams get one slot per stream per subject (teachers assigned per stream).
 */
class CbcRationalizedSubjectSyncService
{
    public function __construct(
        private ClassroomSubjectSlotService $classroomSubjectSlots,
    ) {}

    /**
     * @return array{created: int, migrated_assignments: int, retired_subjects: int, level_type: string, assign_classroom_count: int, codes_for_level: int}
     */
    public function syncLevel(
        string $selectedLevel,
        bool $assignToClassrooms,
        array $classroomIds,
    ): array {
        $this->seedCanonicalSubjects();

        return $this->runAssignmentsForLevel($selectedLevel, $assignToClassrooms, $classroomIds);
    }

    /**
     * @return array{created: int, migrated_assignments: int, retired_subjects: int, levels_processed: int}
     */
    public function syncAllLevels(
        bool $assignToClassrooms,
        array $classroomIds,
    ): array {
        $this->seedCanonicalSubjects();
        $totalAssigned = 0;
        $levels = $this->allGradeLevels();

        foreach ($levels as $level) {
            $r = $this->runAssignmentsForLevel($level, $assignToClassrooms, $classroomIds);
            $totalAssigned += $r['migrated_assignments'];
        }

        return [
            'created' => count($this->getCanonicalCatalogue()),
            'migrated_assignments' => $totalAssigned,
            'retired_subjects' => 0,
            'levels_processed' => count($levels),
        ];
    }

    /**
     * @return array{created: int, migrated_assignments: int, retired_subjects: int, level_type: string, assign_classroom_count: int, codes_for_level: int}
     */
    private function runAssignmentsForLevel(
        string $selectedLevel,
        bool $assignToClassrooms,
        array $classroomIds,
    ): array {
        $levelType = $this->mapGradeToLevelType($selectedLevel);

        if ($assignToClassrooms && $classroomIds === []) {
            $classroomIds = $this->resolveClassroomIdsForLevel($selectedLevel);
        }

        $assignClassroomCount = count($classroomIds);
        $codes = $this->getCodesForSchoolLevel($selectedLevel);
        $catalogue = $this->getCanonicalCatalogueKeyed();

        $assignedRows = 0;
        DB::transaction(function () use ($codes, $catalogue, $assignToClassrooms, $classroomIds, &$assignedRows) {
            if (! $assignToClassrooms || $classroomIds === []) {
                return;
            }
            foreach ($codes as $code) {
                $def = $catalogue[$code] ?? null;
                if (! $def || ! empty($def['is_optional'])) {
                    continue;
                }
                $subject = Subject::where('code', $code)->first();
                if (! $subject) {
                    continue;
                }
                $lpw = isset($def['lessons_per_week']) ? (int) $def['lessons_per_week'] : null;
                foreach ($classroomIds as $classroomId) {
                    $attrs = ['is_compulsory' => true];
                    if ($lpw !== null) {
                        $attrs['lessons_per_week'] = $lpw;
                    }
                    $assignedRows += $this->classroomSubjectSlots->ensureSlotsForClassroomAndSubject(
                        $classroomId,
                        $subject->id,
                        $attrs
                    );
                }
            }
        });

        return [
            'created' => count($this->getCanonicalCatalogue()),
            'migrated_assignments' => $assignedRows,
            'retired_subjects' => 0,
            'level_type' => $levelType,
            'assign_classroom_count' => $assignClassroomCount,
            'codes_for_level' => count($codes),
        ];
    }

    /**
     * Delete all subject-linked rows and all subjects, then seed the canonical catalogue.
     * Destroys exams, marks, homework, timetables, schemes, lesson plans, etc. tied to subjects.
     */
    public function wipeAllSubjectsAndReseed(bool $assignToClassrooms = true): array
    {
        $this->wipeSubjectRelatedData();

        $this->seedCanonicalSubjects();

        if ($assignToClassrooms) {
            return $this->syncAllLevels(true, []);
        }

        return [
            'created' => count($this->getCanonicalCatalogue()),
            'migrated_assignments' => 0,
            'retired_subjects' => 0,
            'levels_processed' => count($this->allGradeLevels()),
        ];
    }

    public function wipeSubjectRelatedData(): void
    {
        $tablesDelete = [
            'exam_marks',
            'exam_schedules',
            'exam_papers',
            'exam_class_subject',
            'exams',
            'homework',
            'timetables',
            'schemes_of_work',
            'lesson_plans',
            'portfolio_assessments',
            'assessments',
            'subject_reports',
            'subject_teacher',
            'classroom_subjects',
            'classroom_subject',
        ];

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tablesDelete as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if (Schema::hasTable('curriculum_designs')) {
                DB::table('curriculum_designs')->update(['subject_id' => null]);
            }
            if (Schema::hasTable('attendance') && Schema::hasColumn('attendance', 'subject_id')) {
                DB::table('attendance')->update(['subject_id' => null]);
            }

            if (Schema::hasTable('subjects')) {
                DB::table('subjects')->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function seedCanonicalSubjects(): int
    {
        $n = 0;
        foreach ($this->getCanonicalCatalogue() as $row) {
            $meta = array_filter([
                'cbc_lessons_per_week' => $row['lessons_per_week'] ?? null,
                'cbc_total_weekly_periods' => $row['total_weekly_periods'] ?? null,
                'cbc_notes' => $row['notes'] ?? null,
                'cbc_curriculum' => 'Kenya CBC (single row per subject code)',
            ], fn ($v) => $v !== null && $v !== '');

            Subject::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'learning_area' => $row['learning_area'] ?? null,
                    'level' => null,
                    'is_active' => true,
                    'is_optional' => $row['is_optional'] ?? false,
                    'meta' => $meta,
                ]
            );
            $n++;
        }

        return $n;
    }

    /**
     * @return list<array{code: string, name: string, learning_area: ?string, is_optional: bool, lessons_per_week: ?int, total_weekly_periods: ?int, notes: ?string}>
     */
    public function getCanonicalCatalogue(): array
    {
        $t = 40;
        $optional = [
            ['code' => 'FRE', 'name' => 'French', 'learning_area' => 'Languages (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'GER', 'name' => 'German', 'learning_area' => 'Languages (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'ARAB', 'name' => 'Arabic', 'learning_area' => 'Languages (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'MAND', 'name' => 'Mandarin (Chinese)', 'learning_area' => 'Languages (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'INDIG', 'name' => 'Indigenous Language', 'learning_area' => 'Languages (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'KSL', 'name' => 'Kenyan Sign Language', 'learning_area' => 'Languages (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'LAT', 'name' => 'Latin', 'learning_area' => 'Languages (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'IRE', 'name' => 'Islamic Religious Education', 'learning_area' => 'Religious (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
            ['code' => 'HRE', 'name' => 'Hindu Religious Education', 'learning_area' => 'Religious (optional)', 'is_optional' => true, 'lessons_per_week' => 3, 'total_weekly_periods' => $t, 'notes' => 'JSS optional'],
        ];

        $core = [
            ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false, 'lessons_per_week' => 5, 'total_weekly_periods' => null, 'notes' => 'PP–JSS; LP uses language-activities style delivery'],
            ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false, 'lessons_per_week' => 4, 'total_weekly_periods' => null, 'notes' => 'Grade 1+'],
            ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false, 'lessons_per_week' => 5, 'total_weekly_periods' => null, 'notes' => null],
            ['code' => 'ENV', 'name' => 'Environmental Activities', 'learning_area' => 'Environmental', 'is_optional' => false, 'lessons_per_week' => 4, 'total_weekly_periods' => null, 'notes' => 'Includes hygiene & nutrition where merged'],
            ['code' => 'CART', 'name' => 'Creative Arts', 'learning_area' => 'Creative Arts', 'is_optional' => false, 'lessons_per_week' => 5, 'total_weekly_periods' => null, 'notes' => 'Art, music, PE strands by phase'],
            ['code' => 'REL', 'name' => 'Religious Education', 'learning_area' => 'Religious', 'is_optional' => false, 'lessons_per_week' => 4, 'total_weekly_periods' => null, 'notes' => 'CRE / IRE / HRE per school policy'],
            ['code' => 'PAST', 'name' => 'Pastoral Programme', 'learning_area' => 'Pastoral', 'is_optional' => false, 'lessons_per_week' => 3, 'total_weekly_periods' => null, 'notes' => null],
            ['code' => 'SCI', 'name' => 'Science', 'learning_area' => 'Science', 'is_optional' => false, 'lessons_per_week' => 5, 'total_weekly_periods' => null, 'notes' => 'UP: Science & Technology; JSS: Integrated Science incl. health'],
            ['code' => 'SS', 'name' => 'Social Studies', 'learning_area' => 'Social', 'is_optional' => false, 'lessons_per_week' => 5, 'total_weekly_periods' => null, 'notes' => 'JSS includes life skills strand'],
            ['code' => 'AGR', 'name' => 'Agriculture & Nutrition', 'learning_area' => 'Agriculture', 'is_optional' => false, 'lessons_per_week' => 4, 'total_weekly_periods' => null, 'notes' => 'UP & JSS'],
            ['code' => 'PRETECH', 'name' => 'Pre-Technical Studies', 'learning_area' => 'Pre-Technical', 'is_optional' => false, 'lessons_per_week' => 4, 'total_weekly_periods' => null, 'notes' => 'JSS: computing, business, pre-tech merged'],
        ];

        return array_merge($core, $optional);
    }

    /**
     * @return array<string, array>
     */
    public function getCanonicalCatalogueKeyed(): array
    {
        $out = [];
        foreach ($this->getCanonicalCatalogue() as $row) {
            $out[$row['code']] = $row;
        }

        return $out;
    }

    /**
     * Which subject codes apply to this grade label.
     *
     * @return list<string>
     */
    public function getCodesForSchoolLevel(string $level): array
    {
        return match ($level) {
            'Foundation', 'PP1', 'PP2' => ['ENG', 'MATH', 'CART', 'ENV', 'REL', 'PAST'],
            'Grade 1', 'Grade 2', 'Grade 3' => ['ENG', 'KIS', 'MATH', 'ENV', 'CART', 'REL', 'PAST'],
            'Grade 4', 'Grade 5', 'Grade 6' => ['ENG', 'KIS', 'MATH', 'SCI', 'SS', 'AGR', 'REL', 'CART'],
            'Grade 7', 'Grade 8', 'Grade 9' => [
                'ENG', 'KIS', 'MATH', 'SCI', 'SS', 'AGR', 'PRETECH', 'CART', 'REL',
                'FRE', 'GER', 'ARAB', 'MAND', 'INDIG', 'KSL', 'LAT', 'IRE', 'HRE',
            ],
            default => [],
        };
    }

    public function getCBCSubjectsForLevel(string $level): array
    {
        $codes = $this->getCodesForSchoolLevel($level);
        $keyed = $this->getCanonicalCatalogueKeyed();
        $out = [];
        foreach ($codes as $code) {
            if (isset($keyed[$code])) {
                $out[] = $keyed[$code];
            }
        }

        return $out;
    }

    public function mapGradeToLevelType(string $grade): string
    {
        $g = Str::lower(trim($grade));

        return match ($g) {
            'foundation', 'pp1', 'pp2' => 'preschool',
            'grade 1', 'grade 2', 'grade 3' => 'lower_primary',
            'grade 4', 'grade 5', 'grade 6' => 'upper_primary',
            'grade 7', 'grade 8', 'grade 9' => 'junior_high',
            default => 'general',
        };
    }

    /**
     * @return list<string>
     */
    public function allGradeLevels(): array
    {
        return [
            'Foundation', 'PP1', 'PP2',
            'Grade 1', 'Grade 2', 'Grade 3',
            'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9',
        ];
    }

    /**
     * Mirror SubjectController::resolveClassroomIdsForLevel (name / level_type fallbacks).
     */
    public function resolveClassroomIdsForLevel(string $level): array
    {
        $normalized = Str::lower(trim($level));
        if ($normalized === '') {
            return [];
        }

        $query = Classroom::query();

        $levelTypeMap = [
            'preschool' => 'preschool',
            'pre-primary' => 'preschool',
            'foundation' => 'preschool',
            'pp1' => 'preschool',
            'pp2' => 'preschool',
            'lower primary' => 'lower_primary',
            'lower_primary' => 'lower_primary',
            'grade 1' => 'lower_primary',
            'grade 2' => 'lower_primary',
            'grade 3' => 'lower_primary',
            'upper primary' => 'upper_primary',
            'upper_primary' => 'upper_primary',
            'grade 4' => 'upper_primary',
            'grade 5' => 'upper_primary',
            'grade 6' => 'upper_primary',
            'junior high' => 'junior_high',
            'junior_high' => 'junior_high',
            'grade 7' => 'junior_high',
            'grade 8' => 'junior_high',
            'grade 9' => 'junior_high',
        ];

        if (isset($levelTypeMap[$normalized])) {
            $levelType = $levelTypeMap[$normalized];
            if (Schema::hasColumn('classrooms', 'level_type')) {
                $query->where('level_type', $levelType);
            }
        } elseif (Schema::hasColumn('classrooms', 'level')) {
            $query->whereRaw('LOWER(level) = ?', [$normalized]);
        } elseif (Schema::hasColumn('classrooms', 'level_key')) {
            $query->whereRaw('LOWER(level_key) = ?', [$normalized]);
        } else {
            $like = $normalized.'%';
            $query->where(function ($q) use ($normalized, $like) {
                $q->whereRaw('LOWER(name) = ?', [$normalized])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$normalized.' %'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['% '.$normalized])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['% '.$normalized.' %']);
            });
        }

        return $query->pluck('id')->unique()->all();
    }
}
