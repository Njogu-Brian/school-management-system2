<?php

namespace App\Services\Academics;

use App\Models\Academics\ReportCard;
use App\Models\User;
use App\Notifications\PDFGeneratedNotification;
use App\Services\Pdf\ChunkedDompdfExporter;
use App\Services\Pdf\PdfBinaryMerger;
use App\Services\ReportCardBatchService;
use App\Support\AcademicContext;
use App\Support\AcademicScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bulk report-card PDFs: class+stream batches, alphabetical within batch, queue when large.
 */
class ReportCardBulkPdfExporter
{
    public function __construct(
        protected ChunkedDompdfExporter $pdfExporter,
        protected PdfBinaryMerger $merger,
    ) {}

    /**
     * @return BinaryFileResponse|Response|\Illuminate\Http\RedirectResponse
     */
    public function respondFromRequest(Request $request, ?int $userId = null)
    {
        $reports = $this->loadSortedReportCards($request);
        if ($reports->isEmpty()) {
            return back()->with('error', 'No report cards found matching the selected criteria.');
        }

        $hardMax = (int) config('pdf_export.hard_max_items', 500);
        if ($reports->count() > $hardMax) {
            return back()->with(
                'error',
                "Too many report cards for PDF export ({$reports->count()}). Maximum is {$hardMax}. Narrow your filters."
            );
        }

        $syncMax = (int) config('pdf_export.sync_max_items', 150);
        $filters = $request->only([
            'academic_year_id',
            'term_id',
            'classroom_id',
            'stream_id',
            'search',
            'ids',
        ]);

        if ($reports->count() > $syncMax) {
            \App\Jobs\GenerateBulkReportCardPdfJob::dispatch($filters, $userId ?? (int) auth()->id());

            return redirect()
                ->route('academics.report_cards.index', array_filter($filters, fn ($v) => $v !== null && $v !== ''))
                ->with(
                    'success',
                    "PDF export queued for {$reports->count()} report cards (class/stream batches). ".
                    'You will get an email/notification with the download link when it is ready.'
                );
        }

        return $this->streamMergedPdf($reports);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function buildQueuedClassBatchZip(array $filters, int $userId): string
    {
        $user = User::find($userId);
        if ($user) {
            Auth::setUser($user);
        }

        $request = Request::create('/academics/report-cards/bulk-print', 'GET', $filters);
        $reports = $this->loadSortedReportCards($request);
        if ($reports->isEmpty()) {
            throw new \RuntimeException('No report cards matched the queued PDF export filters.');
        }

        $groups = $this->groupByClassStream($reports);

        $tmpRoot = storage_path('app/'.trim((string) config('pdf_export.temp_directory', 'pdf-export-tmp'), '/'));
        if (! is_dir($tmpRoot)) {
            mkdir($tmpRoot, 0755, true);
        }

        $runDir = $tmpRoot.DIRECTORY_SEPARATOR.'rc-'.uniqid('', true);
        mkdir($runDir, 0755, true);

        $groupPdfPaths = [];
        $index = 0;

        try {
            foreach ($groups as $groupReports) {
                $index++;
                $label = $this->groupLabel($groupReports->first());
                $safe = Str::slug($label) ?: 'class-'.$index;
                $ids = $groupReports->pluck('id')->values();

                $built = $this->pdfExporter->build($ids, function (Collection $chunkIds) {
                    return $this->pdfExporter->renderView('academics.report_cards.bulk-print-pdf', [
                        'cards' => $this->buildCards($chunkIds),
                    ], 'A4', 'portrait');
                }, [
                    'filename' => sprintf('%02d-%s.pdf', $index, $safe),
                    'chunk_size' => 8,
                    'allow_over_sync' => true,
                    'hard_max' => (int) config('pdf_export.hard_max_items', 500),
                ]);

                $dest = $runDir.DIRECTORY_SEPARATOR.sprintf('%02d-%s.pdf', $index, $safe);
                if (! @rename($built['path'], $dest) && ! @copy($built['path'], $dest)) {
                    throw new \RuntimeException("Failed to stage class batch PDF for {$label}");
                }
                @unlink($built['path']);
                if (! empty($built['cleanup_dir']) && is_dir($built['cleanup_dir'])) {
                    $this->deleteDirectory($built['cleanup_dir']);
                }
                $groupPdfPaths[] = $dest;

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            $stamp = now()->format('Ymd-His');
            $zipName = 'report-cards-class-batches-'.$stamp.'.zip';
            $zipPath = $runDir.DIRECTORY_SEPARATOR.$zipName;

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create report-card class-batch ZIP.');
            }
            foreach ($groupPdfPaths as $path) {
                $zip->addFile($path, basename($path));
            }

            $mergedBase = $runDir.DIRECTORY_SEPARATOR.'report-cards-all-'.$stamp;
            try {
                $merged = $this->merger->mergeOrZip($groupPdfPaths, $mergedBase);
                if ($merged['merged'] && is_file($merged['path'])) {
                    $zip->addFile($merged['path'], 'report-cards-all-merged.'.$merged['extension']);
                }
            } catch (\Throwable $e) {
                Log::info('Optional merged report-card PDF skipped', ['error' => $e->getMessage()]);
            }

            $zip->close();

            $storagePath = 'pdfs/report-card-exports/'.$zipName;
            storage_public()->put($storagePath, file_get_contents($zipPath));
            $url = storage_public_url($storagePath) ?? \Illuminate\Support\Facades\Storage::url($storagePath);

            $user = User::find($userId);
            if ($user) {
                $user->notify(new PDFGeneratedNotification($url, $zipName));
            }

            Log::info('Queued bulk report-card PDF ready', [
                'user_id' => $userId,
                'reports' => $reports->count(),
                'batches' => count($groupPdfPaths),
                'path' => $storagePath,
            ]);

            return $storagePath;
        } finally {
            $this->deleteDirectory($runDir);
        }
    }

    /**
     * @param  Collection<int, ReportCard>  $reports
     */
    public function streamMergedPdf(Collection $reports): BinaryFileResponse|Response
    {
        $groups = $this->groupByClassStream($reports);
        // One DomPDF call per class/stream group so stream batches stay intact across chunks.
        $groupIdLists = $groups->map(fn (Collection $group) => $group->pluck('id')->values())->values();

        return $this->pdfExporter->stream($groupIdLists, function (Collection $chunk) {
            $ids = $chunk->first() ?? collect();
            $idList = $ids instanceof Collection ? $ids : collect($ids);

            return $this->pdfExporter->renderView('academics.report_cards.bulk-print-pdf', [
                'cards' => $this->buildCards($idList),
            ], 'A4', 'portrait');
        }, [
            'filename' => 'report-cards-bulk-'.now()->format('Ymd-His').'.pdf',
            'chunk_size' => 1,
            'allow_over_sync' => true,
        ]);
    }

    public function loadSortedReportCards(Request $request): Collection
    {
        $query = ReportCard::with(['student.classroom', 'student.stream', 'classroom', 'stream', 'academicYear', 'term']);

        $assignedClassroomIds = $this->assignedClassroomIdsForCurrentUser();
        $filterIds = AcademicContext::listFilterIds(
            $request->has('academic_year_id'),
            $request->filled('academic_year_id'),
            $request->filled('academic_year_id') ? (int) $request->academic_year_id : null,
            $request->has('term_id'),
            $request->filled('term_id'),
            $request->filled('term_id') ? (int) $request->term_id : null,
        );

        if ($assignedClassroomIds !== null) {
            if ($assignedClassroomIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('classroom_id', $assignedClassroomIds);
            }
        }

        if ($filterIds['applyYearFilter'] && $filterIds['yearId']) {
            $query->where('academic_year_id', $filterIds['yearId']);
        }
        if ($filterIds['applyTermFilter'] && $filterIds['termId']) {
            $query->where('term_id', $filterIds['termId']);
        }
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('stream_id')) {
            $streamId = (int) $request->stream_id;
            $query->where(function ($q) use ($streamId) {
                $q->where('stream_id', $streamId)
                    ->orWhere(function ($inner) use ($streamId) {
                        $inner->whereNull('stream_id')
                            ->whereHas('student', fn ($s) => $s->where('stream_id', $streamId));
                    });
            });
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('archive', 0)->where('is_alumni', false)
                    ->where(function ($studentQuery) use ($search) {
                        $studentQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('admission_number', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", ["%{$search}%"]);
                    });
            });
        }

        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', (string) $request->ids);
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if ($ids !== []) {
                $query->whereIn('id', $ids);
            }
        }

        return $this->sortReportCardsForExport($query->get());
    }

    /**
     * @param  Collection<int, ReportCard>  $reports
     * @return Collection<int, ReportCard>
     */
    public function sortReportCardsForExport(Collection $reports): Collection
    {
        return $reports->sortBy(function (ReportCard $report) {
            $s = $report->student;
            $class = strtolower(trim((string) ($report->classroom?->name ?? $s?->classroom?->name ?? '~~~')));
            $stream = strtolower(trim((string) ($report->stream?->name ?? $s?->stream?->name ?? '')));
            $first = strtolower(trim((string) ($s?->first_name ?? '')));
            $middle = strtolower(trim((string) ($s?->middle_name ?? '')));
            $last = strtolower(trim((string) ($s?->last_name ?? '')));

            return sprintf('%s|%s|%s|%s|%s', $class, $stream, $first, $middle, $last);
        })->values();
    }

    /**
     * @param  Collection<int, ReportCard>  $reports
     * @return Collection<string, Collection<int, ReportCard>>
     */
    public function groupByClassStream(Collection $reports): Collection
    {
        return $reports
            ->groupBy(fn (ReportCard $report) => $this->classStreamKey($report))
            ->map(fn (Collection $group) => $group->values());
    }

    public function classStreamKey(ReportCard $report): string
    {
        $s = $report->student;
        $classId = (string) ($report->classroom_id ?? $s?->classroom_id ?? '0');
        $streamId = (string) ($report->stream_id ?? $s?->stream_id ?? '0');
        $className = strtolower(trim((string) ($report->classroom?->name ?? $s?->classroom?->name ?? 'unknown')));
        $streamName = strtolower(trim((string) ($report->stream?->name ?? $s?->stream?->name ?? '')));

        return $classId.'|'.$streamId.'|'.$className.'|'.$streamName;
    }

    public function groupLabel(ReportCard $report): string
    {
        $s = $report->student;
        $className = trim((string) ($report->classroom?->name ?? $s?->classroom?->name ?? 'Unknown class'));
        $streamName = trim((string) ($report->stream?->name ?? $s?->stream?->name ?? ''));

        return $streamName !== '' ? "{$className} - {$streamName}" : $className;
    }

    /**
     * @param  Collection<int, int|string>  $ids
     * @return list<array{report_card: ReportCard, dto: array<string, mixed>}>
     */
    protected function buildCards(Collection $ids): array
    {
        $cards = [];
        foreach ($ids as $reportId) {
            $id = (int) $reportId;
            if ($id < 1) {
                continue;
            }
            $report = ReportCard::with(['student.classroom', 'student.stream', 'classroom', 'stream', 'academicYear', 'term'])
                ->find($id);
            if (! $report) {
                continue;
            }
            $cards[] = [
                'report_card' => $report,
                'dto' => ReportCardBatchService::build($id),
            ];
        }

        return $cards;
    }

    /**
     * @return array<int>|null
     */
    protected function assignedClassroomIdsForCurrentUser(): ?array
    {
        $user = Auth::user();
        if (! $user || AcademicScope::seesEveryClass($user) || ! $user->hasRole('Teacher')) {
            return null;
        }

        $staff = $user->staff;
        if (! $staff) {
            return [];
        }

        return array_map('intval', $user->getAssignedClassroomIds());
    }

    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
