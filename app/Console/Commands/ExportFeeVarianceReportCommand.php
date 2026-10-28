<?php

namespace App\Console\Commands;

use App\Exports\ArrayExport;
use App\Models\Academics\Classroom;
use App\Models\Student;
use App\Models\Votehead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExportFeeVarianceReportCommand extends Command
{
    protected $signature = 'finance:export-fee-variance
                            {--from-year=2026}
                            {--from-term=3}
                            {--to-year=2027}
                            {--to-term=1}
                            {--path= : Relative path under storage/app}';

    protected $description = 'Excel variance report for Tuition, Transport, and Drinking Water between two year/terms';

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

        $amounts = $this->loadAmounts(
            $voteheads->pluck('id')->all(),
            [[$fromYear, $fromTerm], [$toYear, $toTerm]]
        );

        $studentIds = collect($amounts)->keys()->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $students = Student::withAlumni()
            ->with(['classroom:id,name', 'stream:id,name'])
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        // Also include active students with neither invoice (so zeros show) — optional; stick to those with either period.
        $fromLabel = "{$fromYear} T{$fromTerm}";
        $toLabel = "{$toYear} T{$toTerm}";

        $headers = [
            'Admission No',
            'Student Name',
            'Current Class',
            'Stream',
            'Status',
        ];
        foreach ($voteheads as $vh) {
            $short = $this->shortName($vh);
            $headers[] = "{$short} {$fromLabel}";
            $headers[] = "{$short} {$toLabel}";
            $headers[] = "{$short} Variance";
        }
        $headers[] = "Total {$fromLabel}";
        $headers[] = "Total {$toLabel}";
        $headers[] = 'Total Variance';

        $rows = [];
        $totals = [
            'from' => array_fill_keys($voteheads->pluck('id')->all(), 0.0),
            'to' => array_fill_keys($voteheads->pluck('id')->all(), 0.0),
        ];

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);
            $name = $student
                ? trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? ''))
                : "#{$studentId}";
            $name = preg_replace('/\s+/', ' ', $name);

            $status = 'Active';
            if ($student) {
                if ($student->is_alumni) {
                    $status = 'Alumni';
                } elseif ((int) $student->archive === 1) {
                    $status = 'Archived';
                }
            }

            $row = [
                $student->admission_number ?? '',
                $name,
                $student?->classroom?->name ?? '',
                $student?->stream?->name ?? '',
                $status,
            ];

            $fromTotal = 0.0;
            $toTotal = 0.0;
            foreach ($voteheads as $vh) {
                $fromAmt = (float) ($amounts[$studentId][$fromYear][$fromTerm][$vh->id] ?? 0);
                $toAmt = (float) ($amounts[$studentId][$toYear][$toTerm][$vh->id] ?? 0);
                $var = $toAmt - $fromAmt;
                $row[] = round($fromAmt, 2);
                $row[] = round($toAmt, 2);
                $row[] = round($var, 2);
                $fromTotal += $fromAmt;
                $toTotal += $toAmt;
                $totals['from'][$vh->id] += $fromAmt;
                $totals['to'][$vh->id] += $toAmt;
            }
            $row[] = round($fromTotal, 2);
            $row[] = round($toTotal, 2);
            $row[] = round($toTotal - $fromTotal, 2);
            $rows[] = $row;
        }

        // Summary row
        $summary = ['', 'SCHOOL TOTAL', '', '', ''];
        $sumFrom = 0.0;
        $sumTo = 0.0;
        foreach ($voteheads as $vh) {
            $f = $totals['from'][$vh->id];
            $t = $totals['to'][$vh->id];
            $summary[] = round($f, 2);
            $summary[] = round($t, 2);
            $summary[] = round($t - $f, 2);
            $sumFrom += $f;
            $sumTo += $t;
        }
        $summary[] = round($sumFrom, 2);
        $summary[] = round($sumTo, 2);
        $summary[] = round($sumTo - $sumFrom, 2);
        $rows[] = $summary;

        usort($rows, function ($a, $b) {
            if (($a[1] ?? '') === 'SCHOOL TOTAL') {
                return 1;
            }
            if (($b[1] ?? '') === 'SCHOOL TOTAL') {
                return -1;
            }

            return strcmp((string) ($a[2] ?? ''), (string) ($b[2] ?? ''))
                ?: strcmp((string) ($a[1] ?? ''), (string) ($b[1] ?? ''));
        });

        $path = $this->option('path') ?: sprintf(
            'reports/fee_variance_%s_t%s_vs_%s_t%s_%s.xlsx',
            $fromYear,
            $fromTerm,
            $toYear,
            $toTerm,
            now()->format('Ymd_His')
        );

        Excel::store(new ArrayExport($rows, $headers), $path, 'local');
        $full = storage_path('app/'.$path);
        $this->info("Wrote {$full}");
        $this->info('Students: '.$studentIds->count());
        $this->info(sprintf('School total variance: %s', number_format($sumTo - $sumFrom, 2)));

        return self::SUCCESS;
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
     * @param  list<int>  $voteheadIds
     * @param  list<array{0:int,1:int}>  $periods
     * @return array<int, array<int, array<int, array<int, float>>>> student → year → term → votehead → amount
     */
    private function loadAmounts(array $voteheadIds, array $periods): array
    {
        $out = [];
        foreach ($periods as [$year, $term]) {
            $rows = DB::table('invoice_items as ii')
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
                ->selectRaw('i.student_id, ii.votehead_id, SUM(COALESCE(ii.original_amount, ii.amount)) as amt')
                ->get();

            foreach ($rows as $row) {
                $sid = (int) $row->student_id;
                $vid = (int) $row->votehead_id;
                $out[$sid][$year][$term][$vid] = (float) $row->amt;
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
