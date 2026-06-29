<?php

namespace App\Jobs;

use App\Models\ExpenseStatementImport;
use App\Services\Finance\ExpenseStatementImportService;
use App\Services\Finance\MpesaExpenseStatementParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ParseExpenseStatementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Pages parsed per python invocation — keeps peak memory bounded. */
    public const PAGES_PER_CHUNK = 5;

    public $tries = 1;
    public $timeout = 1800; // 30 min ceiling; chunked work is far faster

    public function __construct(
        protected int $importId,
        protected ?string $password,
        protected int $totalPages,
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('parse_statement:' . $this->importId))
                ->dontRelease()
                ->expireAfter($this->timeout + 300),
        ];
    }

    public static function progressKey(int $importId): string
    {
        return "expense_parse_progress:{$importId}";
    }

    public static function getProgress(int $importId): array
    {
        return Cache::get(self::progressKey($importId), [
            'status' => 'queued',
            'percent' => 0,
            'processed_pages' => 0,
            'total_pages' => 0,
            'message' => 'Waiting to start…',
        ]);
    }

    protected function setProgress(array $data): void
    {
        $existing = Cache::get(self::progressKey($this->importId), []);
        Cache::put(self::progressKey($this->importId), array_merge($existing, $data), now()->addHour());
    }

    public function handle(ExpenseStatementImportService $service, MpesaExpenseStatementParser $parser): void
    {
        $import = ExpenseStatementImport::find($this->importId);
        if (! $import) {
            return;
        }

        $absolutePath = storage_local_path(config('filesystems.private_disk', 'private'), $import->file_path);
        $total = max(1, $this->totalPages);

        $this->setProgress([
            'status' => 'processing',
            'percent' => 1,
            'processed_pages' => 0,
            'total_pages' => $total,
            'message' => "Parsing 0 of {$total} pages…",
        ]);

        $allTransactions = [];
        $metadata = [];

        for ($start = 1; $start <= $total; $start += self::PAGES_PER_CHUNK) {
            $end = min($start + self::PAGES_PER_CHUNK - 1, $total);

            $result = $parser->parseRange($absolutePath, $this->password, $start, $end);

            if (! ($result['success'] ?? false)) {
                $error = $result['error'] ?? 'parse_failed';
                $message = $result['message'] ?? 'Failed to parse statement.';
                $this->failImport($import, $error, $message);

                return;
            }

            foreach (($result['transactions'] ?? []) as $txn) {
                $allTransactions[] = $txn;
            }

            if (empty($metadata) && ! empty($result['metadata'])) {
                $metadata = $result['metadata'];
            }

            // Reserve the last 8% for the database persistence step.
            $percent = (int) round(($end / $total) * 92);
            $this->setProgress([
                'status' => 'processing',
                'percent' => max(1, min(92, $percent)),
                'processed_pages' => $end,
                'total_pages' => $total,
                'message' => "Parsing {$end} of {$total} pages…",
            ]);
        }

        $this->setProgress([
            'status' => 'saving',
            'percent' => 95,
            'message' => 'Saving transactions…',
        ]);

        try {
            $outcome = $service->persistParsedTransactions($import, $allTransactions, $metadata);
        } catch (\Throwable $e) {
            Log::error('Expense statement persistence failed', [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
            ]);
            $this->failImport($import, 'persist_failed', 'Parsed the statement but failed to save: ' . $e->getMessage());

            return;
        }

        $message = 'Statement parsed successfully.';
        if (($outcome['line_count'] ?? 0) === 0 && ($outcome['duplicates'] ?? 0) > 0) {
            $message = "This statement was already imported — all {$outcome['duplicates']} transaction(s) are duplicates.";
        } elseif (($outcome['duplicates'] ?? 0) > 0) {
            $message .= " {$outcome['duplicates']} duplicate transaction(s) were skipped.";
        }

        $this->setProgress([
            'status' => 'completed',
            'percent' => 100,
            'message' => $message,
            'line_count' => $outcome['line_count'] ?? 0,
            'duplicates' => $outcome['duplicates'] ?? 0,
            'redirect_url' => route('finance.expense-statements.show', $import->id),
        ]);

        Log::info('Expense statement parsed (async)', [
            'import_id' => $this->importId,
            'lines' => $outcome['line_count'] ?? 0,
            'duplicates' => $outcome['duplicates'] ?? 0,
        ]);
    }

    protected function failImport(ExpenseStatementImport $import, string $error, string $message): void
    {
        $import->update([
            'status' => ExpenseStatementImport::STATUS_FAILED,
            'parse_error' => $message,
        ]);

        $this->setProgress([
            'status' => 'failed',
            'percent' => 100,
            'error' => $error,
            'message' => $message,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Expense statement parse job failed', [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
        ]);

        $import = ExpenseStatementImport::find($this->importId);
        if ($import && $import->status !== ExpenseStatementImport::STATUS_PARSED) {
            $import->update([
                'status' => ExpenseStatementImport::STATUS_FAILED,
                'parse_error' => $exception->getMessage(),
            ]);
        }

        $this->setProgress([
            'status' => 'failed',
            'percent' => 100,
            'message' => 'Parsing failed: ' . $exception->getMessage(),
        ]);
    }
}
