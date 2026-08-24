<?php

namespace App\Services\Students;

use App\Models\Admissions\AdmissionApplication;
use App\Models\OnlineAdmission;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class StudentDuplicateDetector
{
    private const REASON_PRIORITY = [
        'nemis' => 100,
        'knec' => 90,
        'admission_number' => 80,
        'name_dob_gender' => 50,
        'name_dob' => 40,
    ];

    private const REASON_LABELS = [
        'nemis' => 'Same NEMIS number',
        'knec' => 'Same KNEC assessment number',
        'admission_number' => 'Same admission number',
        'name_dob_gender' => 'Same name, date of birth, and gender',
        'name_dob' => 'Same name and date of birth',
    ];

    /**
     * @param  array<string, mixed>  $candidate
     * @return Collection<int, StudentDuplicateMatch>
     */
    public function findStudentMatches(array $candidate, ?int $excludeStudentId = null): Collection
    {
        $best = [];

        foreach ($this->matchStudentsByIdentifier($candidate, 'nemis_number', 'nemis', $excludeStudentId) as $match) {
            $this->keepBest($best, $match);
        }
        foreach ($this->matchStudentsByIdentifier($candidate, 'knec_assessment_number', 'knec', $excludeStudentId) as $match) {
            $this->keepBest($best, $match);
        }
        foreach ($this->matchStudentsByAdmissionNumber($candidate, $excludeStudentId) as $match) {
            $this->keepBest($best, $match);
        }
        foreach ($this->matchStudentsByNameDob($candidate, $excludeStudentId) as $match) {
            $this->keepBest($best, $match);
        }

        return collect(array_values($best))->sortByDesc(
            fn (StudentDuplicateMatch $m) => self::REASON_PRIORITY[$m->reason] ?? 0
        )->values();
    }

    /**
     * Pending / in-progress applications that look like the same child.
     *
     * @param  array<string, mixed>  $candidate
     * @param  array{online_admission?: int|null, admission_application?: int|null}  $exclude
     * @return Collection<int, StudentDuplicateMatch>
     */
    public function findApplicationMatches(array $candidate, array $exclude = []): Collection
    {
        $best = [];

        foreach ($this->matchOnlineAdmissions($candidate, $exclude['online_admission'] ?? null) as $match) {
            $this->keepBest($best, $match);
        }
        foreach ($this->matchWebsiteApplications($candidate, $exclude['admission_application'] ?? null) as $match) {
            $this->keepBest($best, $match);
        }

        return collect(array_values($best))->sortByDesc(
            fn (StudentDuplicateMatch $m) => self::REASON_PRIORITY[$m->reason] ?? 0
        )->values();
    }

    /**
     * Students plus open applications (used by staff review / live check).
     *
     * @param  array<string, mixed>  $candidate
     * @param  array{online_admission?: int|null, admission_application?: int|null}  $excludeApplications
     * @return Collection<int, StudentDuplicateMatch>
     */
    public function findAllMatches(array $candidate, ?int $excludeStudentId = null, array $excludeApplications = []): Collection
    {
        return $this->findStudentMatches($candidate, $excludeStudentId)
            ->concat($this->findApplicationMatches($candidate, $excludeApplications))
            ->values();
    }

    /**
     * Block create/enroll unless staff confirmed this is a different child.
     *
     * @param  array<string, mixed>  $candidate
     * @return Collection<int, StudentDuplicateMatch>
     */
    public function assertNoStudentDuplicates(array $candidate, bool $confirmed, ?int $excludeStudentId = null): Collection
    {
        $matches = $this->findStudentMatches($candidate, $excludeStudentId);

        if ($matches->isNotEmpty() && ! $confirmed) {
            throw ValidationException::withMessages([
                'student' => $this->blockingMessage($matches),
                'confirm_duplicate' => 'Tick “This is a different child” if you still want to proceed (for example twins).',
            ]);
        }

        return $matches;
    }

    /**
     * Existing double-entry groups already in the student register.
     *
     * @return list<array{reason: string, reason_label: string, confidence: string, key: string, students: list<array<string, mixed>>}>
     */
    public function findExistingStudentDuplicateGroups(): array
    {
        $groups = [];

        foreach ([
            ['column' => 'nemis_number', 'reason' => 'nemis'],
            ['column' => 'knec_assessment_number', 'reason' => 'knec'],
        ] as $rule) {
            $keys = $this->duplicateIdentifierKeys($rule['column']);
            foreach ($keys as $key) {
                $students = $this->studentsByNormalizedIdentifier($rule['column'], $key);
                if ($students->count() < 2) {
                    continue;
                }
                $groups[] = $this->serializeStudentGroup($rule['reason'], $key, $students);
            }
        }

        $nameKeys = Student::withArchived()
            ->whereNotNull('dob')
            ->whereRaw("TRIM(first_name) <> ''")
            ->whereRaw("TRIM(last_name) <> ''")
            ->selectRaw("LOWER(TRIM(first_name)) as fn, LOWER(TRIM(last_name)) as ln, DATE(dob) as d, COUNT(*) as cnt")
            ->groupByRaw('LOWER(TRIM(first_name)), LOWER(TRIM(last_name)), DATE(dob)')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($nameKeys as $row) {
            $students = Student::withArchived()
                ->with(['classroom'])
                ->whereRaw('LOWER(TRIM(first_name)) = ?', [$row->fn])
                ->whereRaw('LOWER(TRIM(last_name)) = ?', [$row->ln])
                ->whereDate('dob', $row->d)
                ->orderBy('id')
                ->get();

            if ($students->count() < 2) {
                continue;
            }

            $genders = $students->map(fn (Student $s) => $this->normalizeGender($s->gender))->filter()->unique();
            $reason = $genders->count() <= 1 ? 'name_dob_gender' : 'name_dob';
            $groups[] = $this->serializeStudentGroup($reason, $row->fn.'|'.$row->ln.'|'.$row->d, $students);
        }

        return $this->mergeGroupsByStudentSet($groups);
    }

    /**
     * Open applications that match a student already on the register.
     *
     * @return list<array{source: string, application_id: int, application_no: ?string, full_name: string, status: ?string, url: ?string, matches: list<array<string, mixed>>}>
     */
    public function findApplicationsMatchingStudents(): array
    {
        $out = [];

        $online = OnlineAdmission::query()
            ->where('enrolled', false)
            ->where(function ($q) {
                $q->whereNull('application_status')
                    ->orWhereNotIn('application_status', ['enrolled', 'rejected']);
            })
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        foreach ($online as $admission) {
            $matches = $this->findStudentMatches($this->candidateFromOnlineAdmission($admission));
            if ($matches->isEmpty()) {
                continue;
            }
            $out[] = [
                'source' => StudentDuplicateMatch::SOURCE_ONLINE_ADMISSION,
                'source_label' => 'Online admission',
                'application_id' => $admission->id,
                'application_no' => null,
                'full_name' => trim($admission->first_name.' '.$admission->last_name),
                'status' => $admission->application_status,
                'url' => Route::has('online-admissions.show') ? route('online-admissions.show', $admission) : null,
                'matches' => $matches->map->toArray()->all(),
            ];
        }

        $apps = AdmissionApplication::query()
            ->whereNotIn('status', [
                AdmissionApplication::STATUS_ENROLLED,
                AdmissionApplication::STATUS_REJECTED,
            ])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        foreach ($apps as $application) {
            $matches = $this->findStudentMatches($this->candidateFromWebsiteApplication($application));
            if ($matches->isEmpty()) {
                continue;
            }
            $out[] = [
                'source' => StudentDuplicateMatch::SOURCE_WEBSITE_APPLICATION,
                'source_label' => 'Website application',
                'application_id' => $application->id,
                'application_no' => $application->application_no,
                'full_name' => $application->child_name,
                'status' => $application->status,
                'url' => Route::has('website.admissions.show') ? route('website.admissions.show', $application) : null,
                'matches' => $matches->map->toArray()->all(),
            ];
        }

        return $out;
    }

    /**
     * Duplicate open applications of the same child (same channel).
     *
     * @return list<array{reason_label: string, applications: list<array<string, mixed>>}>
     */
    public function findDuplicateOpenApplications(): array
    {
        $groups = [];

        $online = OnlineAdmission::query()
            ->where('enrolled', false)
            ->where(function ($q) {
                $q->whereNull('application_status')
                    ->orWhereNotIn('application_status', ['enrolled', 'rejected']);
            })
            ->whereNotNull('dob')
            ->whereRaw("TRIM(first_name) <> ''")
            ->whereRaw("TRIM(last_name) <> ''")
            ->selectRaw("LOWER(TRIM(first_name)) as fn, LOWER(TRIM(last_name)) as ln, DATE(dob) as d, COUNT(*) as cnt")
            ->groupByRaw('LOWER(TRIM(first_name)), LOWER(TRIM(last_name)), DATE(dob)')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($online as $row) {
            $rows = OnlineAdmission::query()
                ->where('enrolled', false)
                ->whereRaw('LOWER(TRIM(first_name)) = ?', [$row->fn])
                ->whereRaw('LOWER(TRIM(last_name)) = ?', [$row->ln])
                ->whereDate('dob', $row->d)
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $groups[] = [
                'reason' => 'name_dob',
                'reason_label' => 'Repeat online applications (same name and date of birth)',
                'applications' => $rows->map(function (OnlineAdmission $a) {
                    return [
                        'source' => StudentDuplicateMatch::SOURCE_ONLINE_ADMISSION,
                        'id' => $a->id,
                        'label' => trim($a->first_name.' '.$a->last_name),
                        'status' => $a->application_status,
                        'submitted' => optional($a->application_date)?->toDateString(),
                        'url' => Route::has('online-admissions.show') ? route('online-admissions.show', $a) : null,
                    ];
                })->all(),
            ];
        }

        $website = AdmissionApplication::query()
            ->whereNotIn('status', [
                AdmissionApplication::STATUS_ENROLLED,
                AdmissionApplication::STATUS_REJECTED,
            ])
            ->whereNotNull('dob')
            ->whereRaw("TRIM(child_name) <> ''")
            ->selectRaw("LOWER(TRIM(child_name)) as n, DATE(dob) as d, COUNT(*) as cnt")
            ->groupByRaw('LOWER(TRIM(child_name)), DATE(dob)')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($website as $row) {
            $rows = AdmissionApplication::query()
                ->whereNotIn('status', [
                    AdmissionApplication::STATUS_ENROLLED,
                    AdmissionApplication::STATUS_REJECTED,
                ])
                ->whereRaw('LOWER(TRIM(child_name)) = ?', [$row->n])
                ->whereDate('dob', $row->d)
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $groups[] = [
                'reason' => 'name_dob',
                'reason_label' => 'Repeat website applications (same child name and date of birth)',
                'applications' => $rows->map(function (AdmissionApplication $a) {
                    return [
                        'source' => StudentDuplicateMatch::SOURCE_WEBSITE_APPLICATION,
                        'id' => $a->id,
                        'label' => $a->child_name.' ('.$a->application_no.')',
                        'status' => $a->status,
                        'submitted' => optional($a->submitted_at)?->toDateString(),
                        'url' => Route::has('website.admissions.show') ? route('website.admissions.show', $a) : null,
                    ];
                })->all(),
            ];
        }

        return $groups;
    }

    public function blockingMessage(Collection $matches): string
    {
        if ($matches->isEmpty()) {
            return 'A possible duplicate was found.';
        }

        $parts = $matches->take(4)->map(function (StudentDuplicateMatch $m) {
            $who = $m->fullName;
            if ($m->admissionNumber) {
                $who .= ' (Admission #'.$m->admissionNumber.')';
            } elseif ($m->applicationNo) {
                $who .= ' ('.$m->applicationNo.')';
            }
            if ($m->status) {
                $who .= ', '.$m->status;
            }

            return $who.' — '.$m->reasonLabel;
        });

        $prefix = $matches->contains(fn (StudentDuplicateMatch $m) => $m->isHighConfidence())
            ? 'This child already appears on the register. '
            : 'Possible duplicate admission. ';

        $extra = $matches->count() > 4 ? ' (+'.($matches->count() - 4).' more)' : '';

        return $prefix.$parts->implode('; ').$extra.'. Open the existing record, or confirm this is a different child.';
    }

    /**
     * Stable key for spotting two rows in the same import file.
     *
     * @param  array<string, mixed>  $candidate
     */
    public function identityKey(array $candidate): ?string
    {
        $nemis = $this->normalizeIdentifier($candidate['nemis_number'] ?? null);
        if ($nemis) {
            return 'nemis:'.$nemis;
        }
        $knec = $this->normalizeIdentifier($candidate['knec_assessment_number'] ?? null);
        if ($knec) {
            return 'knec:'.$knec;
        }
        $nameDob = $this->nameDobKey($candidate);
        if ($nameDob) {
            return 'name:'.$nameDob;
        }
        $admission = $this->normalizeIdentifier($candidate['admission_number'] ?? null);
        if ($admission) {
            return 'adm:'.$admission;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    public function normalizeCandidate(array $candidate): array
    {
        return [
            'first_name' => $this->normalizeName($candidate['first_name'] ?? null),
            'middle_name' => $this->normalizeName($candidate['middle_name'] ?? null),
            'last_name' => $this->normalizeName($candidate['last_name'] ?? null),
            'dob' => $this->normalizeDob($candidate['dob'] ?? null),
            'gender' => $this->normalizeGender($candidate['gender'] ?? null),
            'nemis_number' => $this->normalizeIdentifier($candidate['nemis_number'] ?? null),
            'knec_assessment_number' => $this->normalizeIdentifier($candidate['knec_assessment_number'] ?? null),
            'admission_number' => $this->normalizeIdentifier($candidate['admission_number'] ?? null),
        ];
    }

    public function candidateFromOnlineAdmission(OnlineAdmission $admission): array
    {
        return [
            'first_name' => $admission->first_name,
            'middle_name' => $admission->middle_name,
            'last_name' => $admission->last_name,
            'dob' => $admission->dob,
            'gender' => $admission->gender,
            'nemis_number' => $admission->nemis_number,
            'knec_assessment_number' => $admission->knec_assessment_number,
        ];
    }

    public function candidateFromWebsiteApplication(AdmissionApplication $application): array
    {
        $parts = $application->childNameParts();

        return [
            'first_name' => $parts['first_name'],
            'middle_name' => $parts['middle_name'],
            'last_name' => $parts['last_name'],
            'dob' => $application->dob,
            'gender' => $application->gender,
        ];
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return list<StudentDuplicateMatch>
     */
    private function matchStudentsByIdentifier(array $candidate, string $column, string $reason, ?int $excludeStudentId): array
    {
        $value = $this->normalizeIdentifier($candidate[$column] ?? null);
        if (! $value) {
            return [];
        }

        return $this->studentsByNormalizedIdentifier($column, $value, $excludeStudentId)
            ->map(fn (Student $s) => $this->matchFromStudent($s, $reason, StudentDuplicateMatch::CONFIDENCE_HIGH))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return list<StudentDuplicateMatch>
     */
    private function matchStudentsByAdmissionNumber(array $candidate, ?int $excludeStudentId): array
    {
        $value = $this->normalizeIdentifier($candidate['admission_number'] ?? null);
        if (! $value) {
            return [];
        }

        return $this->studentBaseQuery($excludeStudentId)
            ->whereRaw("LOWER(REPLACE(REPLACE(admission_number, ' ', ''), '-', '')) = ?", [$value])
            ->get()
            ->map(fn (Student $s) => $this->matchFromStudent($s, 'admission_number', StudentDuplicateMatch::CONFIDENCE_HIGH))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return list<StudentDuplicateMatch>
     */
    private function matchStudentsByNameDob(array $candidate, ?int $excludeStudentId): array
    {
        $first = $this->normalizeName($candidate['first_name'] ?? null);
        $last = $this->normalizeName($candidate['last_name'] ?? null);
        $dob = $this->normalizeDob($candidate['dob'] ?? null);
        if (! $first || ! $last || ! $dob) {
            return [];
        }

        $gender = $this->normalizeGender($candidate['gender'] ?? null);

        return $this->studentBaseQuery($excludeStudentId)
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [$first])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [$last])
            ->whereDate('dob', $dob)
            ->get()
            ->map(function (Student $s) use ($gender) {
                $sameGender = $gender && $this->normalizeGender($s->gender) === $gender;
                $reason = $sameGender || ! $gender ? 'name_dob_gender' : 'name_dob';

                return $this->matchFromStudent($s, $reason, StudentDuplicateMatch::CONFIDENCE_MEDIUM);
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return list<StudentDuplicateMatch>
     */
    private function matchOnlineAdmissions(array $candidate, ?int $excludeId): array
    {
        $query = OnlineAdmission::query()
            ->where('enrolled', false)
            ->where(function ($q) {
                $q->whereNull('application_status')
                    ->orWhereNotIn('application_status', ['enrolled', 'rejected']);
            })
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        return $this->collectApplicationMatches(
            $query,
            $candidate,
            function (OnlineAdmission $row, string $reason, string $confidence) {
                return new StudentDuplicateMatch(
                    source: StudentDuplicateMatch::SOURCE_ONLINE_ADMISSION,
                    reason: $reason,
                    reasonLabel: self::REASON_LABELS[$reason],
                    confidence: $confidence,
                    fullName: trim($row->first_name.' '.$row->last_name),
                    sourceLabel: 'Online admission',
                    applicationId: $row->id,
                    applicationStatus: $row->application_status,
                    url: Route::has('online-admissions.show') ? route('online-admissions.show', $row) : null,
                );
            },
            'first_name',
            'last_name',
            'nemis_number',
            'knec_assessment_number',
        );
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return list<StudentDuplicateMatch>
     */
    private function matchWebsiteApplications(array $candidate, ?int $excludeId): array
    {
        $first = $this->normalizeName($candidate['first_name'] ?? null);
        $last = $this->normalizeName($candidate['last_name'] ?? null);
        $dob = $this->normalizeDob($candidate['dob'] ?? null);
        if (! $first || ! $last || ! $dob) {
            return [];
        }

        $rows = AdmissionApplication::query()
            ->whereNotIn('status', [
                AdmissionApplication::STATUS_ENROLLED,
                AdmissionApplication::STATUS_REJECTED,
            ])
            ->whereNotNull('dob')
            ->whereDate('dob', $dob)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get();

        $gender = $this->normalizeGender($candidate['gender'] ?? null);
        $matches = [];

        foreach ($rows as $row) {
            $parts = $row->childNameParts();
            if ($this->normalizeName($parts['first_name']) !== $first || $this->normalizeName($parts['last_name']) !== $last) {
                continue;
            }
            $sameGender = $gender && $this->normalizeGender($row->gender) === $gender;
            $reason = $sameGender || ! $gender ? 'name_dob_gender' : 'name_dob';
            $matches[] = new StudentDuplicateMatch(
                source: StudentDuplicateMatch::SOURCE_WEBSITE_APPLICATION,
                reason: $reason,
                reasonLabel: self::REASON_LABELS[$reason],
                confidence: StudentDuplicateMatch::CONFIDENCE_MEDIUM,
                fullName: $row->child_name,
                sourceLabel: 'Website application',
                applicationId: $row->id,
                applicationNo: $row->application_no,
                applicationStatus: $row->status,
                url: Route::has('website.admissions.show') ? route('website.admissions.show', $row) : null,
            );
        }

        return $matches;
    }

    /**
     * @param  callable(object, string, string): StudentDuplicateMatch  $make
     * @return list<StudentDuplicateMatch>
     */
    private function collectApplicationMatches(
        Builder $query,
        array $candidate,
        callable $make,
        string $firstCol,
        string $lastCol,
        string $nemisCol,
        string $knecCol,
    ): array {
        $matches = [];
        $clone = clone $query;

        $nemis = $this->normalizeIdentifier($candidate['nemis_number'] ?? null);
        if ($nemis) {
            $clone->whereNotNull($nemisCol)
                ->whereRaw("LOWER(REPLACE(REPLACE({$nemisCol}, ' ', ''), '-', '')) = ?", [$nemis])
                ->get()
                ->each(function ($row) use (&$matches, $make) {
                    $matches[] = $make($row, 'nemis', StudentDuplicateMatch::CONFIDENCE_HIGH);
                });
        }

        $knec = $this->normalizeIdentifier($candidate['knec_assessment_number'] ?? null);
        if ($knec) {
            (clone $query)
                ->whereNotNull($knecCol)
                ->whereRaw("LOWER(REPLACE(REPLACE({$knecCol}, ' ', ''), '-', '')) = ?", [$knec])
                ->get()
                ->each(function ($row) use (&$matches, $make) {
                    $matches[] = $make($row, 'knec', StudentDuplicateMatch::CONFIDENCE_HIGH);
                });
        }

        $first = $this->normalizeName($candidate['first_name'] ?? null);
        $last = $this->normalizeName($candidate['last_name'] ?? null);
        $dob = $this->normalizeDob($candidate['dob'] ?? null);
        $gender = $this->normalizeGender($candidate['gender'] ?? null);

        if ($first && $last && $dob) {
            (clone $query)
                ->whereRaw("LOWER(TRIM({$firstCol})) = ?", [$first])
                ->whereRaw("LOWER(TRIM({$lastCol})) = ?", [$last])
                ->whereDate('dob', $dob)
                ->get()
                ->each(function ($row) use (&$matches, $make, $gender) {
                    $sameGender = $gender && $this->normalizeGender($row->gender ?? null) === $gender;
                    $reason = $sameGender || ! $gender ? 'name_dob_gender' : 'name_dob';
                    $matches[] = $make($row, $reason, StudentDuplicateMatch::CONFIDENCE_MEDIUM);
                });
        }

        return $matches;
    }

    private function matchFromStudent(Student $student, string $reason, string $confidence): StudentDuplicateMatch
    {
        $status = 'active';
        if ($student->archive) {
            $status = 'archived';
        } elseif ($student->is_alumni) {
            $status = 'alumni';
        }

        $classroom = $student->classroom?->name;
        if ($student->stream?->name) {
            $classroom = trim(($classroom ?? '').' '.$student->stream->name);
        }

        return new StudentDuplicateMatch(
            source: StudentDuplicateMatch::SOURCE_STUDENT,
            reason: $reason,
            reasonLabel: self::REASON_LABELS[$reason],
            confidence: $confidence,
            fullName: $student->full_name,
            sourceLabel: 'Student register',
            studentId: $student->id,
            admissionNumber: $student->admission_number,
            status: $status,
            classroom: $classroom,
            url: Route::has('students.show') ? route('students.show', $student) : null,
        );
    }

    private function studentBaseQuery(?int $excludeStudentId = null): Builder
    {
        return Student::withArchived()
            ->with(['classroom', 'stream'])
            ->when($excludeStudentId, fn (Builder $q) => $q->where('id', '!=', $excludeStudentId));
    }

    /**
     * @return Collection<int, Student>
     */
    private function studentsByNormalizedIdentifier(string $column, string $normalized, ?int $excludeStudentId = null): Collection
    {
        return $this->studentBaseQuery($excludeStudentId)
            ->whereNotNull($column)
            ->whereRaw("TRIM({$column}) <> ''")
            ->whereRaw("LOWER(REPLACE(REPLACE({$column}, ' ', ''), '-', '')) = ?", [$normalized])
            ->get();
    }

    /**
     * @return list<string>
     */
    private function duplicateIdentifierKeys(string $column): array
    {
        return Student::withArchived()
            ->whereNotNull($column)
            ->whereRaw("TRIM({$column}) <> ''")
            ->selectRaw("LOWER(REPLACE(REPLACE({$column}, ' ', ''), '-', '')) as dup_key")
            ->groupByRaw("LOWER(REPLACE(REPLACE({$column}, ' ', ''), '-', ''))")
            ->havingRaw('COUNT(*) > 1')
            ->pluck('dup_key')
            ->filter(fn ($key) => is_string($key) && strlen($key) >= 3)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return array{reason: string, reason_label: string, confidence: string, key: string, student_ids: string, students: list<array<string, mixed>>}
     */
    private function serializeStudentGroup(string $reason, string $key, Collection $students): array
    {
        $ids = $students->pluck('id')->sort()->values()->implode(',');

        return [
            'reason' => $reason,
            'reason_label' => self::REASON_LABELS[$reason],
            'confidence' => (self::REASON_PRIORITY[$reason] ?? 0) >= 80
                ? StudentDuplicateMatch::CONFIDENCE_HIGH
                : StudentDuplicateMatch::CONFIDENCE_MEDIUM,
            'key' => $key,
            'student_ids' => $ids,
            'students' => $students->map(function (Student $s) {
                $status = 'active';
                if ($s->archive) {
                    $status = 'archived';
                } elseif ($s->is_alumni) {
                    $status = 'alumni';
                }

                return [
                    'id' => $s->id,
                    'full_name' => $s->full_name,
                    'admission_number' => $s->admission_number,
                    'dob' => optional($s->dob)?->toDateString(),
                    'gender' => $s->gender,
                    'nemis_number' => $s->nemis_number,
                    'knec_assessment_number' => $s->knec_assessment_number,
                    'status' => $status,
                    'classroom' => $s->classroom?->name,
                    'url' => Route::has('students.show') ? route('students.show', $s) : null,
                ];
            })->all(),
        ];
    }

    /**
     * @param  list<array{reason: string, reason_label: string, confidence: string, key: string, student_ids: string, students: list<array<string, mixed>>}>  $groups
     * @return list<array{reason: string, reason_label: string, confidence: string, key: string, students: list<array<string, mixed>>, reasons: list<string>}>
     */
    private function mergeGroupsByStudentSet(array $groups): array
    {
        $merged = [];

        foreach ($groups as $group) {
            $idKey = $group['student_ids'];
            if (! isset($merged[$idKey])) {
                $merged[$idKey] = $group;
                $merged[$idKey]['reasons'] = [$group['reason_label']];
                unset($merged[$idKey]['student_ids']);
                continue;
            }

            if ((self::REASON_PRIORITY[$group['reason']] ?? 0) > (self::REASON_PRIORITY[$merged[$idKey]['reason']] ?? 0)) {
                $merged[$idKey]['reason'] = $group['reason'];
                $merged[$idKey]['reason_label'] = $group['reason_label'];
                $merged[$idKey]['confidence'] = $group['confidence'];
            }
            $merged[$idKey]['reasons'][] = $group['reason_label'];
            $merged[$idKey]['reasons'] = array_values(array_unique($merged[$idKey]['reasons']));
        }

        return array_values($merged);
    }

    /**
     * @param  array<string, StudentDuplicateMatch>  $best
     */
    private function keepBest(array &$best, StudentDuplicateMatch $match): void
    {
        $key = $match->source.':'.($match->studentId ?? $match->applicationId ?? $match->fullName);
        if (! isset($best[$key])) {
            $best[$key] = $match;

            return;
        }

        $current = self::REASON_PRIORITY[$best[$key]->reason] ?? 0;
        $incoming = self::REASON_PRIORITY[$match->reason] ?? 0;
        if ($incoming > $current) {
            $best[$key] = $match;
        }
    }

    private function normalizeName(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''), 'UTF-8');

        return $value === '' ? null : $value;
    }

    private function normalizeIdentifier(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = mb_strtolower(preg_replace('/[\s\-]/', '', (string) $value) ?? '', 'UTF-8');
        if ($value === '' || $value === '0' || strlen($value) < 3) {
            return null;
        }

        return $value;
    }

    private function normalizeGender(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = mb_strtolower(trim((string) $value), 'UTF-8');
        if (in_array($value, ['m', 'male', 'boy'], true)) {
            return 'male';
        }
        if (in_array($value, ['f', 'female', 'girl'], true)) {
            return 'female';
        }

        return $value === '' ? null : $value;
    }

    private function normalizeDob(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function nameDobKey(array $candidate): ?string
    {
        $first = $this->normalizeName($candidate['first_name'] ?? null);
        $last = $this->normalizeName($candidate['last_name'] ?? null);
        $dob = $this->normalizeDob($candidate['dob'] ?? null);
        if (! $first || ! $last || ! $dob) {
            return null;
        }

        return $first.'|'.$last.'|'.$dob;
    }
}
