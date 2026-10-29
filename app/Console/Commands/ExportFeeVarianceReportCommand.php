<?php

namespace App\Console\Commands;

use App\Exports\FeeVarianceWorkbookExport;
use App\Models\Student;
use App\Models\Votehead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExportFeeVarianceReportCommand extends Command
{
    /** Normal tuition step when moving PP2→Grade 1 or Grade 6→Grade 7. */
    private const CLASS_STEP_TUITION = 3000.0;

    protected $signature = 'finance:export-fee-variance
                            {--from-year=2026}
                            {--from-term=3}
                            {--to-year=2027}
                            {--to-term=1}
                            {--notes-csv= : CSV of known Term credit/debit notes to exclude matching variances}
                            {--path= : Relative path under storage/app}';

    protected $description = 'Excel variance report for Tuition, Transport, and Drinking Water between two year/terms (net of credit/debit notes)';

    public function handle(): int
    {
        $fromYear = (int) $this->option('from-year');
        $fromTerm = (int) $this->option('from-term');
        $toYear = (int) $this->option('to-year');
        $toTerm = (int) $this->option('to-term');

        $voteheads = $this->resolveVoteheads();
        if ($voteheads->isEmpty()) {
            $this->error('Could not resolve Tuition / Transport / Drinking Water voteheads.');

            return self::FAILURE;
        }

        $this->info('Voteheads: '.$voteheads->map(fn ($v) => "{$v->id}={$v->name}")->implode(', '));
        $this->info('Amounts = original fee − credit notes + debit notes');

        $notesCsv = (string) ($this->option('notes-csv') ?: '');
        $expectedNotes = $this->loadExpectedNotesVariance($notesCsv, $voteheads);
        if ($notesCsv !== '') {
            $this->info('Known notes loaded for '.count($expectedNotes).' admission/votehead keys');
        }

        $amounts = $this->loadAmounts(
            $voteheads->pluck('id')->all(),
            [[$fromYear, $fromTerm], [$toYear, $toTerm]]
        );

        $studentIds = collect($amounts)->keys()->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $students = Student::withAlumni()
            ->with(['classroom:id,name', 'stream:id,name', 'family:id,guardian_name,father_name,mother_name'])
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $fromLabel = "{$fromYear} T{$fromTerm}";
        $toLabel = "{$toYear} T{$toTerm}";

        $headers = $this->buildHeaders($voteheads, $fromLabel, $toLabel);
        $detailRows = [];
        $rowMeta = []; // student_id => computed row + variance flags
        $totals = [
            'from' => array_fill_keys($voteheads->pluck('id')->all(), 0.0),
            'to' => array_fill_keys($voteheads->pluck('id')->all(), 0.0),
        ];

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);
            $built = $this->buildStudentRow(
                $studentId,
                $student,
                $voteheads,
                $amounts,
                $fromYear,
                $fromTerm,
                $toYear,
                $toTerm,
                $totals
            );
            $detailRows[] = $built['row'];
            $rowMeta[$studentId] = $built;
        }

        $summary = $this->buildSummaryRow($voteheads, $totals);
        $sumFrom = (float) $summary[count($summary) - 3];
        $sumTo = (float) $summary[count($summary) - 2];
        $detailRows[] = $summary;

        usort($detailRows, function ($a, $b) {
            if (($a[1] ?? '') === 'SCHOOL TOTAL') {
                return 1;
            }
            if (($b[1] ?? '') === 'SCHOOL TOTAL') {
                return -1;
            }

            return strcmp((string) ($a[2] ?? ''), (string) ($b[2] ?? ''))
                ?: strcmp((string) ($a[1] ?? ''), (string) ($b[1] ?? ''));
        });

        $siblingSheet = $this->buildSiblingDifferenceSheet(
            $rowMeta,
            $students,
            $voteheads,
            $headers,
            $fromLabel,
            $toLabel
        );

        $unmatchedSheet = $this->buildUnmatchedNotesSheet(
            $rowMeta,
            $voteheads,
            $expectedNotes,
            $fromLabel,
            $toLabel
        );

        $path = $this->option('path') ?: sprintf(
            'reports/fee_variance_%s_t%s_vs_%s_t%s_%s.xlsx',
            $fromYear,
            $fromTerm,
            $toYear,
            $toTerm,
            now()->format('Ymd_His')
        );

        Excel::store(new FeeVarianceWorkbookExport([
            [
                'title' => 'All Students',
                'headers' => $headers,
                'rows' => $detailRows,
            ],
            [
                'title' => 'Siblings With Differences',
                'headers' => $siblingSheet['headers'],
                'rows' => $siblingSheet['rows'],
            ],
            [
                'title' => 'Unmatched vs Notes File',
                'headers' => $unmatchedSheet['headers'],
                'rows' => $unmatchedSheet['rows'],
            ],
        ]), $path, 'local');

        $full = storage_path('app/private/'.$path);
        if (! is_file($full)) {
            $full = storage_path('app/'.$path);
        }
        $this->info("Wrote {$full}");
        $this->info('Students: '.$studentIds->count());
        $this->info('Sibling families with differences: '.$siblingSheet['family_count']);
        $this->info('Students skipped (matches notes and/or +3000 class step): '.$unmatchedSheet['matched_count']);
        $this->info('Excluded alumni / Grade 9 / no 2027 invoice: '.($unmatchedSheet['excluded_alumni'] ?? 0));
        $this->info('Students on Unmatched vs Notes File: '.$unmatchedSheet['unmatched_count']);
        $this->info(sprintf(
            'School total variance (net of notes): %s',
            number_format($sumTo - $sumFrom, 2)
        ));

        return self::SUCCESS;
    }

    private function buildHeaders($voteheads, string $fromLabel, string $toLabel): array
    {
        $headers = [
            'Admission No',
            'Student Name',
            'Current Class',
            'Stream',
            'Status',
            'Family ID',
        ];
        foreach ($voteheads as $vh) {
            $short = $this->shortName($vh);
            $headers[] = "{$short} {$fromLabel} (net)";
            $headers[] = "{$short} {$fromLabel} credits";
            $headers[] = "{$short} {$fromLabel} debits";
            $headers[] = "{$short} {$toLabel} (net)";
            $headers[] = "{$short} {$toLabel} credits";
            $headers[] = "{$short} {$toLabel} debits";
            $headers[] = "{$short} Variance";
        }
        $headers[] = "Total {$fromLabel} (net)";
        $headers[] = "Total {$toLabel} (net)";
        $headers[] = 'Total Variance';

        return $headers;
    }

    private function buildStudentRow(
        int $studentId,
        ?Student $student,
        $voteheads,
        array $amounts,
        int $fromYear,
        int $fromTerm,
        int $toYear,
        int $toTerm,
        array &$totals
    ): array {
        $name = $student
            ? trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? ''))
            : "#{$studentId}";
        $name = preg_replace('/\s+/', ' ', (string) $name);

        $status = 'Active';
        if ($student) {
            if ($student->is_alumni) {
                $status = 'Alumni';
            } elseif ((int) $student->archive === 1) {
                $status = 'Archived';
            }
        }

        $familyKey = $student?->family_id ?: ($student?->archived_family_id ?: null);
        $className = (string) ($student?->classroom?->name ?? '');
        $classNorm = strtoupper(trim($className));

        $row = [
            $student->admission_number ?? '',
            $name,
            $className,
            $student?->stream?->name ?? '',
            $status,
            $familyKey ?? '',
        ];

        $fromTotal = 0.0;
        $toTotal = 0.0;
        $hasDifference = false;
        $variances = [];
        $hasToInvoice = false;

        foreach ($voteheads as $vh) {
            $from = $amounts[$studentId][$fromYear][$fromTerm][$vh->id] ?? ['net' => 0.0, 'credits' => 0.0, 'debits' => 0.0];
            $to = $amounts[$studentId][$toYear][$toTerm][$vh->id] ?? ['net' => 0.0, 'credits' => 0.0, 'debits' => 0.0];
            $fromAmt = (float) $from['net'];
            $toAmt = (float) $to['net'];
            if (abs($toAmt) >= 0.01 || abs((float) $to['credits']) >= 0.01 || abs((float) $to['debits']) >= 0.01) {
                $hasToInvoice = true;
            }
            $var = $toAmt - $fromAmt;
            if (abs($var) >= 0.01) {
                $hasDifference = true;
            }
            $variances[$vh->id] = $var;

            $row[] = round($fromAmt, 2);
            $row[] = round((float) $from['credits'], 2);
            $row[] = round((float) $from['debits'], 2);
            $row[] = round($toAmt, 2);
            $row[] = round((float) $to['credits'], 2);
            $row[] = round((float) $to['debits'], 2);
            $row[] = round($var, 2);

            $fromTotal += $fromAmt;
            $toTotal += $toAmt;
            $totals['from'][$vh->id] += $fromAmt;
            $totals['to'][$vh->id] += $toAmt;
        }

        $totalVar = $toTotal - $fromTotal;
        if (abs($totalVar) >= 0.01) {
            $hasDifference = true;
        }

        $row[] = round($fromTotal, 2);
        $row[] = round($toTotal, 2);
        $row[] = round($totalVar, 2);

        // PP2→Grade 1 and Grade 6→Grade 7 tuition structures normally jump by 3,000.
        $classStepTuition = 0.0;
        $classStepLabel = '';
        if ($classNorm === 'GRADE 1') {
            $classStepTuition = self::CLASS_STEP_TUITION;
            $classStepLabel = 'PP2→Grade 1 (+'.number_format(self::CLASS_STEP_TUITION, 0).' tuition)';
        } elseif ($classNorm === 'GRADE 7') {
            $classStepTuition = self::CLASS_STEP_TUITION;
            $classStepLabel = 'Grade 6→Grade 7 (+'.number_format(self::CLASS_STEP_TUITION, 0).' tuition)';
        }

        return [
            'student_id' => $studentId,
            'admission_number' => strtoupper(trim((string) ($student->admission_number ?? ''))),
            'classroom_name' => $className,
            'is_alumni' => (bool) ($student?->is_alumni),
            'has_to_invoice' => $hasToInvoice,
            'class_step_tuition' => $classStepTuition,
            'class_step_label' => $classStepLabel,
            'family_key' => $familyKey ? (int) $familyKey : null,
            'row' => $row,
            'has_difference' => $hasDifference,
            'total_variance' => $totalVar,
            'variances' => $variances,
            'from_total' => $fromTotal,
            'to_total' => $toTotal,
        ];
    }

    /**
     * Expected T1−T3 variance explained by known notes file.
     * Credit on from-term lowers from-net ⇒ positive expected variance.
     * Debit on from-term raises from-net ⇒ negative expected variance.
     *
     * @return array<string, array<int, float>> admission => votehead_id => expected variance
     */
    private function loadExpectedNotesVariance(string $csvPath, $voteheads): array
    {
        if ($csvPath === '') {
            return [];
        }

        $candidates = [
            $csvPath,
            storage_path('app/'.$csvPath),
            storage_path('app/private/'.$csvPath),
            base_path($csvPath),
        ];
        $resolved = null;
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $resolved = $path;
                break;
            }
        }
        if (! $resolved) {
            $this->warn("Notes CSV not found: {$csvPath}");

            return [];
        }

        $voteheadByName = [];
        foreach ($voteheads as $vh) {
            $voteheadByName[strtoupper(trim((string) $vh->name))] = (int) $vh->id;
            $voteheadByName[strtoupper(trim((string) $vh->code))] = (int) $vh->id;
        }

        $fh = fopen($resolved, 'r');
        if (! $fh) {
            $this->warn("Could not open notes CSV: {$resolved}");

            return [];
        }

        $header = null;
        $out = [];
        while (($data = fgetcsv($fh)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $data);
                continue;
            }
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $adm = strtoupper(trim((string) ($row['admission_number'] ?? '')));
            $vhName = strtoupper(trim((string) ($row['votehead_name'] ?? '')));
            $type = strtoupper(trim((string) ($row['type'] ?? '')));
            $amount = (float) str_replace([',', ' '], '', (string) ($row['amount'] ?? 0));
            if ($adm === '' || $vhName === '' || $amount == 0.0) {
                continue;
            }

            $voteheadId = $voteheadByName[$vhName] ?? null;
            if (! $voteheadId) {
                // soft match: tuition / transport / drinking water
                foreach ($voteheads as $vh) {
                    $short = strtoupper($this->shortName($vh));
                    if (str_contains($vhName, $short) || str_contains($short, $vhName)) {
                        $voteheadId = (int) $vh->id;
                        break;
                    }
                    if (str_contains($vhName, 'TUITION') && str_contains(strtoupper((string) $vh->name), 'TUITION')) {
                        $voteheadId = (int) $vh->id;
                        break;
                    }
                }
            }
            if (! $voteheadId) {
                continue;
            }

            // Cr on from-term ⇒ +amount expected variance; Dr ⇒ -amount
            $delta = 0.0;
            if (in_array($type, ['CR', 'CREDIT', 'C'], true)) {
                $delta = $amount;
            } elseif (in_array($type, ['DR', 'DEBIT', 'D'], true)) {
                $delta = -$amount;
            } else {
                // default treat as credit if type blank
                $delta = $amount;
            }

            $out[$adm][$voteheadId] = ($out[$adm][$voteheadId] ?? 0.0) + $delta;
        }
        fclose($fh);

        return $out;
    }

    /**
     * Students with fee differences NOT fully explained by:
     * - known Term 3 credit/debit notes file, and/or
     * - normal class-step tuition (+3000 for PP2→Grade 1 or Grade 6→Grade 7).
     *
     * Unexplained amounts are residuals AFTER those expected items.
     * Grade 9 / alumni (no 2027 invoice) are excluded.
     */
    private function buildUnmatchedNotesSheet(
        array $rowMeta,
        $voteheads,
        array $expectedNotes,
        string $fromLabel,
        string $toLabel
    ): array {
        $tuitionId = null;
        foreach ($voteheads as $vh) {
            if (str_contains(strtoupper((string) $vh->name), 'TUITION')) {
                $tuitionId = (int) $vh->id;
                break;
            }
        }

        $headers = [
            'Admission No',
            'Student Name',
            'Current Class',
            'Stream',
            'Status',
            'Family ID',
            'Class Step Adjustment',
            'Unexplained Voteheads (after notes + class step)',
            'Notes File Expected',
            'Class Step Expected',
            'Actual Total Variance',
            'Unexplained Residual (excl. 3000 class step)',
        ];
        foreach ($voteheads as $vh) {
            $short = $this->shortName($vh);
            $headers[] = "{$short} Variance";
            $headers[] = "{$short} Notes Expected";
            $headers[] = "{$short} Class Step Expected";
            $headers[] = "{$short} Unexplained Residual";
        }

        $rows = [];
        $matchedCount = 0;
        $unmatchedCount = 0;
        $excludedAlumni = 0;

        foreach ($rowMeta as $meta) {
            $classNorm = strtoupper(trim((string) ($meta['classroom_name'] ?? '')));
            $isAlumni = (bool) ($meta['is_alumni'] ?? false);
            $hasToInvoice = (bool) ($meta['has_to_invoice'] ?? false);

            // Grade 9 / alumni / no 2027 invoice — not unexpected
            if ($isAlumni || $classNorm === 'GRADE 9' || ! $hasToInvoice) {
                $excludedAlumni++;
                continue;
            }

            if (! ($meta['has_difference'] ?? false)) {
                continue;
            }

            $adm = (string) ($meta['admission_number'] ?? '');
            $expectedByVh = $expectedNotes[$adm] ?? [];
            $classStepTuition = (float) ($meta['class_step_tuition'] ?? 0);
            $unexplainedParts = [];
            $notesExpectedTotal = 0.0;
            $classStepTotal = 0.0;
            $residualTotal = 0.0;
            $perVh = [];
            $allExplained = true;

            foreach ($voteheads as $vh) {
                $vid = (int) $vh->id;
                $actual = (float) ($meta['variances'][$vid] ?? 0);
                $notesExpected = (float) ($expectedByVh[$vid] ?? 0);
                $stepExpected = ($tuitionId && $vid === $tuitionId) ? $classStepTuition : 0.0;
                $expected = $notesExpected + $stepExpected;
                $residual = $actual - $expected;

                $notesExpectedTotal += $notesExpected;
                $classStepTotal += $stepExpected;
                $residualTotal += $residual;
                $perVh[$vid] = [
                    'actual' => $actual,
                    'notes_expected' => $notesExpected,
                    'step_expected' => $stepExpected,
                    'residual' => $residual,
                ];

                if (abs($actual) < 0.01 && abs($expected) < 0.01) {
                    continue;
                }

                if (abs($residual) >= 0.01) {
                    $allExplained = false;
                    $unexplainedParts[] = $this->shortName($vh).' ('.number_format($residual, 2).')';
                }
            }

            if ($allExplained) {
                $matchedCount++;
                continue; // skip — explained by notes and/or normal 3000 class step
            }

            $unmatchedCount++;
            $base = $meta['row'];
            $row = [
                $base[0] ?? '',
                $base[1] ?? '',
                $base[2] ?? '',
                $base[3] ?? '',
                $base[4] ?? '',
                $base[5] ?? '',
                (string) ($meta['class_step_label'] ?? ''),
                implode('; ', $unexplainedParts),
                round($notesExpectedTotal, 2),
                round($classStepTotal, 2),
                round((float) ($meta['total_variance'] ?? 0), 2),
                round($residualTotal, 2),
            ];
            foreach ($voteheads as $vh) {
                $p = $perVh[(int) $vh->id];
                $row[] = round($p['actual'], 2);
                $row[] = round($p['notes_expected'], 2);
                $row[] = round($p['step_expected'], 2);
                $row[] = round($p['residual'], 2);
            }
            $rows[] = $row;
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($a[2] ?? ''), (string) ($b[2] ?? ''))
            ?: strcmp((string) ($a[1] ?? ''), (string) ($b[1] ?? '')));

        if ($unmatchedCount === 0) {
            $rows[] = ['No unexplained differences after notes file + class-step 3000 adjustments.', '', '', '', '', '', '', '', '', '', '', ''];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
            'excluded_alumni' => $excludedAlumni,
        ];
    }

    private function buildSummaryRow($voteheads, array $totals): array
    {
        $summary = ['', 'SCHOOL TOTAL', '', '', '', ''];
        $sumFrom = 0.0;
        $sumTo = 0.0;
        foreach ($voteheads as $vh) {
            $f = $totals['from'][$vh->id];
            $t = $totals['to'][$vh->id];
            $summary[] = round($f, 2);
            $summary[] = ''; // credits N/A on total
            $summary[] = ''; // debits N/A on total
            $summary[] = round($t, 2);
            $summary[] = '';
            $summary[] = '';
            $summary[] = round($t - $f, 2);
            $sumFrom += $f;
            $sumTo += $t;
        }
        $summary[] = round($sumFrom, 2);
        $summary[] = round($sumTo, 2);
        $summary[] = round($sumTo - $sumFrom, 2);

        return $summary;
    }

    /**
     * Families with 2+ children in the report where at least one child has a fee variance.
     */
    private function buildSiblingDifferenceSheet(
        array $rowMeta,
        $students,
        $voteheads,
        array $baseHeaders,
        string $fromLabel,
        string $toLabel
    ): array {
        $byFamily = collect($rowMeta)
            ->filter(fn ($m) => ! empty($m['family_key']))
            ->groupBy('family_key');

        $headers = array_merge(['Family Group'], $baseHeaders);
        $rows = [];
        $familyCount = 0;

        $eligible = $byFamily->filter(function ($members) {
            if ($members->count() < 2) {
                return false;
            }

            // Family has a term-to-term difference, OR siblings differ from each other on net totals/fees
            $hasTermDiff = $members->contains(fn ($m) => $m['has_difference']);
            $toTotals = $members->pluck('to_total')->map(fn ($v) => round((float) $v, 2))->unique();
            $fromTotals = $members->pluck('from_total')->map(fn ($v) => round((float) $v, 2))->unique();
            $siblingsDiffer = $toTotals->count() > 1 || $fromTotals->count() > 1;

            return $hasTermDiff || $siblingsDiffer;
        })->sortKeys();

        foreach ($eligible as $familyId => $members) {
            $familyCount++;
            $family = $students->first(fn ($s) => (int) ($s->family_id ?: $s->archived_family_id) === (int) $familyId)?->family;
            $familyName = trim((string) ($family?->guardian_name ?: $family?->father_name ?: $family?->mother_name ?: ''));
            $label = 'Family #'.$familyId.($familyName !== '' ? ' — '.$familyName : '');

            $rows[] = [$label, '', '', '', '', '', ''];

            $sorted = $members->sortBy(fn ($m) => $m['row'][1] ?? '');
            foreach ($sorted as $m) {
                $rows[] = array_merge([$label], $m['row']);
            }

            $sub = $this->familySubtotalRow($members, $voteheads);
            $rows[] = array_merge([$label.' TOTAL'], $sub);
            $rows[] = array_fill(0, count($headers), '');
        }

        if ($familyCount === 0) {
            $rows[] = ['No sibling groups with fee differences found.', '', '', '', '', '', ''];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'family_count' => $familyCount,
        ];
    }

    private function familySubtotalRow($members, $voteheads): array
    {
        $row = ['', 'FAMILY TOTAL', '', '', '', ''];
        $sumFrom = 0.0;
        $sumTo = 0.0;

        foreach ($voteheads as $idx => $vh) {
            // Each votehead block in student row starts at column index 6, width 7
            $base = 6 + ($idx * 7);
            $fromNet = $members->sum(fn ($m) => (float) ($m['row'][$base] ?? 0));
            $fromCredits = $members->sum(fn ($m) => (float) ($m['row'][$base + 1] ?? 0));
            $fromDebits = $members->sum(fn ($m) => (float) ($m['row'][$base + 2] ?? 0));
            $toNet = $members->sum(fn ($m) => (float) ($m['row'][$base + 3] ?? 0));
            $toCredits = $members->sum(fn ($m) => (float) ($m['row'][$base + 4] ?? 0));
            $toDebits = $members->sum(fn ($m) => (float) ($m['row'][$base + 5] ?? 0));
            $var = $toNet - $fromNet;

            $row[] = round($fromNet, 2);
            $row[] = round($fromCredits, 2);
            $row[] = round($fromDebits, 2);
            $row[] = round($toNet, 2);
            $row[] = round($toCredits, 2);
            $row[] = round($toDebits, 2);
            $row[] = round($var, 2);

            $sumFrom += $fromNet;
            $sumTo += $toNet;
        }

        $row[] = round($sumFrom, 2);
        $row[] = round($sumTo, 2);
        $row[] = round($sumTo - $sumFrom, 2);

        return $row;
    }

    private function resolveVoteheads()
    {
        $queries = [
            'tuition' => Votehead::query()->where(function ($q) {
                $q->whereRaw('UPPER(name) like ?', ['%TUITION%'])
                    ->orWhereRaw('UPPER(code) like ?', ['%TUITION%']);
            }),
            'transport' => Votehead::query()->where(function ($q) {
                $q->whereRaw('UPPER(code) = ?', ['TRANSPORT'])
                    ->orWhereRaw('UPPER(name) = ?', ['TRANSPORT']);
            }),
            'drinking_water' => Votehead::query()->where(function ($q) {
                $q->whereRaw('UPPER(code) like ?', ['%DRINKING%WATER%'])
                    ->orWhereRaw('UPPER(name) like ?', ['%DRINKING%WATER%']);
            }),
        ];

        $found = collect();
        foreach ($queries as $key => $query) {
            $vh = $query->orderBy('id')->first();
            if ($vh) {
                $found->put($key, $vh);
            } else {
                $this->warn("Missing votehead for {$key}");
            }
        }

        return $found->values();
    }

    /**
     * Net amount = SUM(COALESCE(original_amount, amount)) − credits + debits.
     *
     * @param  list<int>  $voteheadIds
     * @param  list<array{0:int,1:int}>  $periods
     * @return array<int, array<int, array<int, array<int, array{net:float,credits:float,debits:float}>>>>
     */
    private function loadAmounts(array $voteheadIds, array $periods): array
    {
        $out = [];
        foreach ($periods as [$year, $term]) {
            $baseRows = DB::table('invoice_items as ii')
                ->join('invoices as i', 'i.id', '=', 'ii.invoice_id')
                ->whereNull('i.deleted_at')
                ->whereNull('i.reversed_at')
                ->where(function ($q) {
                    $q->whereNull('i.status')->orWhere('i.status', '<>', 'reversed');
                })
                ->whereNull('ii.deleted_at')
                ->where('ii.status', '!=', 'reversed')
                ->where('i.year', $year)
                ->where('i.term', $term)
                ->whereIn('ii.votehead_id', $voteheadIds)
                ->groupBy('i.student_id', 'ii.votehead_id')
                ->selectRaw('i.student_id, ii.votehead_id, SUM(COALESCE(ii.original_amount, ii.amount)) as base_amt, GROUP_CONCAT(ii.id) as item_ids')
                ->get();

            $allItemIds = [];
            foreach ($baseRows as $row) {
                foreach (explode(',', (string) $row->item_ids) as $id) {
                    if ($id !== '') {
                        $allItemIds[] = (int) $id;
                    }
                }
            }
            $allItemIds = array_values(array_unique($allItemIds));

            $creditsByItem = [];
            $debitsByItem = [];
            if ($allItemIds) {
                $creditQ = DB::table('credit_notes')->whereIn('invoice_item_id', $allItemIds);
                if (DB::getSchemaBuilder()->hasColumn('credit_notes', 'deleted_at')) {
                    $creditQ->whereNull('deleted_at');
                }
                foreach ($creditQ->selectRaw('invoice_item_id, SUM(amount) as amt')->groupBy('invoice_item_id')->get() as $c) {
                    $creditsByItem[(int) $c->invoice_item_id] = (float) $c->amt;
                }

                $debitQ = DB::table('debit_notes')->whereIn('invoice_item_id', $allItemIds);
                if (DB::getSchemaBuilder()->hasColumn('debit_notes', 'deleted_at')) {
                    $debitQ->whereNull('deleted_at');
                }
                foreach ($debitQ->selectRaw('invoice_item_id, SUM(amount) as amt')->groupBy('invoice_item_id')->get() as $d) {
                    $debitsByItem[(int) $d->invoice_item_id] = (float) $d->amt;
                }
            }

            foreach ($baseRows as $row) {
                $sid = (int) $row->student_id;
                $vid = (int) $row->votehead_id;
                $itemIds = array_filter(array_map('intval', explode(',', (string) $row->item_ids)));
                $credits = 0.0;
                $debits = 0.0;
                foreach ($itemIds as $iid) {
                    $credits += $creditsByItem[$iid] ?? 0.0;
                    $debits += $debitsByItem[$iid] ?? 0.0;
                }
                $base = (float) $row->base_amt;
                $out[$sid][$year][$term][$vid] = [
                    'net' => $base - $credits + $debits,
                    'credits' => $credits,
                    'debits' => $debits,
                ];
            }
        }

        return $out;
    }

    private function shortName(Votehead $vh): string
    {
        $n = strtoupper((string) $vh->name);
        if (str_contains($n, 'TUITION')) {
            return 'Tuition';
        }
        if (str_contains($n, 'TRANSPORT')) {
            return 'Transport';
        }
        if (str_contains($n, 'DRINKING') || str_contains($n, 'WATER')) {
            return 'Drinking Water';
        }

        return $vh->name;
    }
}
