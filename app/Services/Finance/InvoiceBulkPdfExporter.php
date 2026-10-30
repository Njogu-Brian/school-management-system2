<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Notifications\PDFGeneratedNotification;
use App\Services\Pdf\ChunkedDompdfExporter;
use App\Services\Pdf\PdfBinaryMerger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bulk invoice PDFs: class+stream batches, alphabetical within batch, queue when large.
 */
class InvoiceBulkPdfExporter
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
        $invoices = $this->loadSortedInvoices($request);
        if ($invoices->isEmpty()) {
            return back()->with('error', 'No invoices found for the selected criteria.');
        }

        $hardMax = (int) config('pdf_export.hard_max_items', 500);
        if ($invoices->count() > $hardMax) {
            return back()->with(
                'error',
                "Too many invoices for PDF export ({$invoices->count()}). Maximum is {$hardMax}. Narrow your filters."
            );
        }

        $syncMax = (int) config('pdf_export.sync_max_items', 150);
        $filters = $request->only(['year', 'term', 'votehead_id', 'class_id', 'stream_id', 'student_id', 'status']);

        if ($invoices->count() > $syncMax) {
            \App\Jobs\GenerateBulkInvoicePdfJob::dispatch($filters, $userId ?? (int) auth()->id());

            return back()->with(
                'success',
                "PDF export queued for {$invoices->count()} invoices (class/stream batches). ".
                'You will get an email/notification with the download link when it is ready.'
            );
        }

        return $this->streamMergedPdf($invoices, $filters, optional(auth()->user())->name ?? 'System');
    }

    /**
     * Build class/stream ZIP for a queued job and notify the user.
     *
     * @param  array<string, mixed>  $filters
     */
    public function buildQueuedClassBatchZip(array $filters, int $userId): string
    {
        $request = Request::create('/finance/invoices/print', 'GET', $filters);
        $invoices = $this->loadSortedInvoices($request);
        if ($invoices->isEmpty()) {
            throw new \RuntimeException('No invoices matched the queued PDF export filters.');
        }

        $groups = $this->groupByClassStream($invoices);
        $branding = $this->branding();
        $printedBy = optional(User::find($userId))->name ?? 'System';
        $printedAt = now();
        $invoiceHeader = \App\Models\Setting::get('invoice_header', '');
        $invoiceFooterTemplate = \App\Models\Setting::get('invoice_footer', '');

        $tmpRoot = storage_path('app/'.trim((string) config('pdf_export.temp_directory', 'pdf-export-tmp'), '/'));
        if (!is_dir($tmpRoot)) {
            mkdir($tmpRoot, 0755, true);
        }

        $runDir = $tmpRoot.DIRECTORY_SEPARATOR.'inv-'.uniqid('', true);
        mkdir($runDir, 0755, true);

        $groupPdfPaths = [];
        $index = 0;

        try {
            foreach ($groups as $groupKey => $groupInvoices) {
                $index++;
                $bundles = $this->buildBundles($groupInvoices);
                $label = $this->groupLabel($groupInvoices->first());
                $safe = Str::slug($label) ?: 'class-'.$index;

                $built = $this->pdfExporter->build($bundles, function (Collection $chunk) use (
                    $filters,
                    $branding,
                    $printedBy,
                    $printedAt,
                    $invoiceHeader,
                    $invoiceFooterTemplate
                ) {
                    return $this->pdfExporter->renderView('finance.invoices.pdf.bulk', [
                        'invoiceBundles' => $chunk,
                        'filters' => $filters,
                        'branding' => $branding,
                        'printedBy' => $printedBy,
                        'printedAt' => $printedAt,
                        'invoiceHeader' => $invoiceHeader,
                        'invoiceFooterTemplate' => $invoiceFooterTemplate,
                    ], 'A4', 'portrait');
                }, [
                    'filename' => sprintf('%02d-%s.pdf', $index, $safe),
                    'chunk_size' => max(1, (int) config('pdf_export.chunk_size', 15)),
                    'allow_over_sync' => true,
                    'hard_max' => (int) config('pdf_export.hard_max_items', 500),
                ]);

                $dest = $runDir.DIRECTORY_SEPARATOR.sprintf('%02d-%s.pdf', $index, $safe);
                if (!@rename($built['path'], $dest) && !@copy($built['path'], $dest)) {
                    throw new \RuntimeException("Failed to stage class batch PDF for {$label}");
                }
                @unlink($built['path']);
                if (!empty($built['cleanup_dir']) && is_dir($built['cleanup_dir'])) {
                    $this->deleteDirectory($built['cleanup_dir']);
                }
                $groupPdfPaths[] = $dest;

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            $stamp = now()->format('Ymd-His');
            $zipName = 'invoices-class-batches-'.$stamp.'.zip';
            $zipPath = $runDir.DIRECTORY_SEPARATOR.$zipName;

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create invoice class-batch ZIP.');
            }
            foreach ($groupPdfPaths as $path) {
                $zip->addFile($path, basename($path));
            }

            // Also include a single merged PDF when qpdf/FPDI can merge (full print run).
            $mergedBase = $runDir.DIRECTORY_SEPARATOR.'invoices-all-'.$stamp;
            try {
                $merged = $this->merger->mergeOrZip($groupPdfPaths, $mergedBase);
                if ($merged['merged'] && is_file($merged['path'])) {
                    $zip->addFile($merged['path'], 'invoices-all-merged.'.$merged['extension']);
                }
            } catch (\Throwable $e) {
                Log::info('Optional merged invoice PDF skipped', ['error' => $e->getMessage()]);
            }

            $zip->close();

            $storagePath = 'pdfs/invoice-exports/'.$zipName;
            storage_public()->put($storagePath, file_get_contents($zipPath));
            $url = storage_public_url($storagePath) ?? \Illuminate\Support\Facades\Storage::url($storagePath);

            $user = User::find($userId);
            if ($user) {
                $user->notify(new PDFGeneratedNotification($url, $zipName));
            }

            Log::info('Queued bulk invoice PDF ready', [
                'user_id' => $userId,
                'invoices' => $invoices->count(),
                'batches' => count($groupPdfPaths),
                'path' => $storagePath,
            ]);

            return $storagePath;
        } finally {
            $this->deleteDirectory($runDir);
        }
    }

    /**
     * Sync response: one merged PDF, rendered per class/stream batch then merged.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @param  array<string, mixed>  $filters
     */
    public function streamMergedPdf(Collection $invoices, array $filters, string $printedBy): BinaryFileResponse|Response
    {
        $groups = $this->groupByClassStream($invoices);
        $branding = $this->branding();
        $printedAt = now();
        $invoiceHeader = \App\Models\Setting::get('invoice_header', '');
        $invoiceFooterTemplate = \App\Models\Setting::get('invoice_footer', '');

        // Flatten to ordered bundles but render one class/stream group per DomPDF call
        // so banners never reset mid-class across chunks.
        $groupBundles = [];
        foreach ($groups as $groupInvoices) {
            $groupBundles[] = $this->buildBundles($groupInvoices);
        }

        return $this->pdfExporter->stream($groupBundles, function (Collection $chunk) use (
            $filters,
            $branding,
            $printedBy,
            $printedAt,
            $invoiceHeader,
            $invoiceFooterTemplate
        ) {
            // Each "item" is already one class/stream bundle list.
            $bundles = $chunk->first() ?? collect();

            return $this->pdfExporter->renderView('finance.invoices.pdf.bulk', [
                'invoiceBundles' => $bundles instanceof Collection ? $bundles : collect($bundles),
                'filters' => $filters,
                'branding' => $branding,
                'printedBy' => $printedBy,
                'printedAt' => $printedAt,
                'invoiceHeader' => $invoiceHeader,
                'invoiceFooterTemplate' => $invoiceFooterTemplate,
            ], 'A4', 'portrait');
        }, [
            'filename' => 'invoices-'.now()->format('Ymd-His').'.pdf',
            'chunk_size' => 1,
            'allow_over_sync' => true,
        ]);
    }

    public function loadSortedInvoices(Request $request): Collection
    {
        $query = Invoice::query()
            ->with([
                'student.classroom',
                'student.stream',
                'student.family',
                'classroom',
                'stream',
                'items.votehead',
                'items.allocations.payment.paymentMethod',
                'term',
                'academicYear',
            ])
            ->when($request->input('status') !== 'reversed', fn ($q) => $q->notReversed())
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->year))
            ->when($request->filled('term'), fn ($q) => $q->where('term', $request->term))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('votehead_id'), fn ($q) =>
                $q->whereHas('items', fn ($i) => $i->where('votehead_id', $request->votehead_id))
            )
            ->when($request->filled('class_id'), function ($q) use ($request) {
                $classId = (int) $request->class_id;
                $q->where(function ($inner) use ($classId) {
                    $inner->where('classroom_id', $classId)
                        ->orWhere(function ($legacy) use ($classId) {
                            $legacy->whereNull('classroom_id')
                                ->whereHas('student', fn ($s) => $s->where('classroom_id', $classId)->where('archive', 0)->where('is_alumni', false));
                        });
                });
            })
            ->when($request->filled('stream_id'), function ($q) use ($request) {
                $streamId = (int) $request->stream_id;
                $q->where(function ($inner) use ($streamId) {
                    $inner->where('stream_id', $streamId)
                        ->orWhere(function ($legacy) use ($streamId) {
                            $legacy->whereNull('stream_id')
                                ->whereHas('student', fn ($s) => $s->where('stream_id', $streamId)->where('archive', 0)->where('is_alumni', false));
                        });
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        return $this->sortInvoicesForExport($query->get());
    }

    /**
     * Class → stream (streams are separate batches) → first → middle → last name.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    public function sortInvoicesForExport(Collection $invoices): Collection
    {
        return $invoices->sortBy(function (Invoice $invoice) {
            $s = $invoice->student;
            if (!$s) {
                return '~~~|~~~|~~~|~~~|~~~';
            }
            $class = strtolower(trim((string) (optional($invoice->classroom)->name ?? optional($s->classroom)->name ?? '~~~')));
            $stream = strtolower(trim((string) (optional($invoice->stream)->name ?? optional($s->stream)->name ?? '')));
            // Empty stream sorts before named streams within a class, still its own batch key.
            $first = strtolower(trim((string) ($s->first_name ?? '')));
            $middle = strtolower(trim((string) ($s->middle_name ?? '')));
            $last = strtolower(trim((string) ($s->last_name ?? '')));

            return sprintf('%s|%s|%s|%s|%s', $class, $stream, $first, $middle, $last);
        })->values();
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<string, Collection<int, Invoice>>
     */
    public function groupByClassStream(Collection $invoices): Collection
    {
        return $invoices
            ->groupBy(fn (Invoice $invoice) => $this->classStreamKey($invoice))
            ->map(fn (Collection $group) => $group->values());
    }

    public function classStreamKey(Invoice $invoice): string
    {
        $s = $invoice->student;
        $classId = (string) ($invoice->classroom_id ?? $s?->classroom_id ?? '0');
        $streamId = (string) ($invoice->stream_id ?? $s?->stream_id ?? '0');
        $className = strtolower(trim(optional($invoice->classroom)->name ?? optional($s?->classroom)->name ?? 'unknown'));
        $streamName = strtolower(trim(optional($invoice->stream)->name ?? optional($s?->stream)->name ?? ''));

        // Prefer ids when present so renames don't split batches mid-export.
        return $classId.'|'.$streamId.'|'.$className.'|'.$streamName;
    }

    public function groupLabel(Invoice $invoice): string
    {
        $s = $invoice->student;
        $className = trim((string) (optional($invoice->classroom)->name ?? optional($s?->classroom)->name ?? 'Unknown class'));
        $streamName = trim((string) (optional($invoice->stream)->name ?? optional($s?->stream)->name ?? ''));

        return $streamName !== '' ? "{$className} - {$streamName}" : $className;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, array<string, mixed>>
     */
    public function buildBundles(Collection $invoices): Collection
    {
        $paymentRowsByInvoice = $this->invoicePaymentRowsForPdfBatch($invoices);
        $priorOverlays = app(InvoicePdfPriorBalanceService::class)->overlayForInvoices($invoices);
        $studentBalances = [];

        return $invoices->map(function (Invoice $invoice) use ($paymentRowsByInvoice, $priorOverlays, &$studentBalances) {
            $invoice->fillTotalsFromLoadedRelations();
            $overlay = $priorOverlays[$invoice->id] ?? [
                'lines' => [],
                'total' => 0.0,
                'pdf_balance_due' => round((float) $invoice->balance, 2),
            ];

            $sid = (int) $invoice->student_id;
            if ($sid > 0 && ! isset($studentBalances[$sid])) {
                $student = $invoice->student
                    ?? \App\Models\Student::withoutGlobalScopes()->find($sid);
                $studentBalances[$sid] = [
                    'overdue' => $student
                        ? max(0.0, (float) \App\Services\StudentBalanceService::getTotalOutstandingBalance($student, true))
                        : 0.0,
                    'upcoming' => $student
                        ? max(0.0, (float) \App\Services\StudentBalanceService::getUpcomingBalance($student))
                        : 0.0,
                ];
            }

            return [
                'invoice' => $invoice,
                'payment_rows' => $paymentRowsByInvoice[$invoice->id] ?? [],
                'prior_balance_lines' => $overlay['lines'],
                'prior_balance_total' => $overlay['total'],
                'pdf_balance_due' => $overlay['pdf_balance_due'],
                'student_overdue_balance' => $studentBalances[$sid]['overdue'] ?? 0.0,
                'student_upcoming_balance' => $studentBalances[$sid]['upcoming'] ?? 0.0,
            ];
        })->values();
    }

    /**
     * @return array<int, array<int, array{date: mixed, receipt: string, method: string, amount: float, is_internal: bool}>>
     */
    protected function invoicePaymentRowsForPdfBatch(Collection $invoices): array
    {
        $ids = $invoices->pluck('id')->filter()->values()->all();
        if ($ids === []) {
            return [];
        }

        $allocations = PaymentAllocation::query()
            ->whereHas('invoiceItem', fn ($q) => $q->whereIn('invoice_id', $ids))
            ->with(['invoiceItem:id,invoice_id', 'payment.paymentMethod'])
            ->get()
            ->filter(fn ($a) => $a->payment && !$a->payment->reversed);

        $byInvoice = [];
        foreach ($allocations as $a) {
            $iid = (int) ($a->invoiceItem->invoice_id ?? 0);
            if ($iid < 1) {
                continue;
            }
            $byInvoice[$iid] ??= collect();
            $byInvoice[$iid]->push($a);
        }

        $out = [];
        foreach ($ids as $invoiceId) {
            $groups = ($byInvoice[$invoiceId] ?? collect())->groupBy('payment_id');
            $rows = [];
            foreach ($groups as $group) {
                $payment = $group->first()->payment;
                $rows[] = [
                    'date' => $payment->payment_date,
                    'receipt' => (string) ($payment->receipt_number ?? ''),
                    'method' => $payment->paymentMethod->name ?? $payment->payment_method ?? '-',
                    'amount' => round((float) $group->sum('amount'), 2),
                    'is_internal' => ($payment->payment_channel ?? '') === 'term_balance_transfer',
                ];
            }
            usort($rows, function ($a, $b) {
                $da = $a['date'] ? \Carbon\Carbon::parse($a['date'])->timestamp : 0;
                $db = $b['date'] ? \Carbon\Carbon::parse($b['date'])->timestamp : 0;

                return $da <=> $db;
            });
            $out[$invoiceId] = $rows;
        }

        return $out;
    }

    protected function branding(): array
    {
        $kv = DB::table('settings')->pluck('value', 'key')->map(fn ($v) => trim((string) $v));

        $name = $kv['school_name'] ?? config('app.name', 'Your School');
        $email = $kv['school_email'] ?? 'info@example.com';
        $phone = $kv['school_phone'] ?? '';
        $website = $kv['school_website'] ?? '';
        $address = $kv['school_address'] ?? '';

        $logoFilename = $kv['school_logo'] ?? null;
        $logoPathSetting = $kv['school_logo_path'] ?? null;

        $candidates = [];
        if ($logoFilename) {
            $candidates[] = public_path('images/'.$logoFilename);
        }
        if ($logoPathSetting) {
            $candidates[] = public_path($logoPathSetting);
            $candidates[] = public_path('storage/'.$logoPathSetting);
            $candidates[] = storage_path('app/public/'.$logoPathSetting);
        }
        if ($candidates === []) {
            $candidates[] = public_path('images/logo.png');
        }

        $logoBase64 = null;
        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png');
            if ($mime === 'image/png' && !extension_loaded('gd') && !extension_loaded('imagick')) {
                $logoBase64 = null;
                break;
            }
            $logoBase64 = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            break;
        }

        return compact('name', 'email', 'phone', 'website', 'address', 'logoBase64');
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
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
