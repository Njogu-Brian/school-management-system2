<?php

namespace App\Services;

use App\Models\LegacyFinanceImportBatch;
use App\Models\LegacyLedgerPosting;
use App\Models\LegacyStatementLine;
use App\Models\LegacyStatementTerm;
use App\Models\LegacyVoteheadMapping;
use App\Models\Votehead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LegacyLedgerPostingService
{
    /**
    * Prepare and (eventually) post a legacy batch into finance tables.
    * For now, we focus on votehead mapping guards and idempotency scaffolding.
    */
    public function processBatch(int $batchId): void
    {
        $batch = LegacyFinanceImportBatch::with(['terms', 'terms.lines'])->find($batchId);
        if (!$batch) {
            Log::warning('Legacy posting skipped: batch not found', ['batch_id' => $batchId]);
            return;
        }

        $missing = $this->ensureVoteheadMappings($batch);
        if ($missing->isNotEmpty()) {
            Log::info('Legacy posting halted: votehead mappings required', [
                'batch_id' => $batch->id,
                'missing' => $missing->values(),
            ]);
            return;
        }

        // TODO: Implement full posting (invoices, payments, credits/debits, discounts).
        Log::info('Legacy posting stub: processing will be implemented next', ['batch_id' => $batch->id]);
    }

    /**
     * Find missing voteheads and create pending mapping records.
     */
    public function ensureVoteheadMappings(LegacyFinanceImportBatch $batch): Collection
    {
        $labels = $batch->terms
            ->flatMap(fn (LegacyStatementTerm $term) => $term->lines->pluck('votehead'))
            ->filter()
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        $missing = collect();

        foreach ($labels as $label) {
            $resolved = Votehead::whereRaw('LOWER(name) = ?', [mb_strtolower($label, 'UTF-8')])->first();
            $mapping = LegacyVoteheadMapping::where('legacy_label', $label)->first();

            if ($resolved && (!$mapping || !$mapping->votehead_id)) {
                LegacyVoteheadMapping::updateOrCreate(
                    ['legacy_label' => $label],
                    ['votehead_id' => $resolved->id, 'status' => 'resolved']
                );
                continue;
            }

            if ($mapping && $mapping->status === 'resolved') {
                continue;
            }

            LegacyVoteheadMapping::firstOrCreate(
                ['legacy_label' => $label],
                ['status' => 'pending']
            );

            $missing->push($label);
        }

        return $missing;
    }

    /**
     * Compute a stable hash for a line to guard idempotency.
     */
    public function hashLine(LegacyStatementLine $line): string
    {
        return hash('sha256', implode('|', [
            $line->id,
            $line->term_id,
            $line->txn_type,
            $line->txn_date,
            $line->narration_raw,
            $line->amount_dr,
            $line->amount_cr,
            $line->running_balance,
            $line->votehead,
        ]));
    }

    /**
     * Record a posting attempt to keep idempotency.
     */
    public function recordPosting(
        LegacyStatementLine $line,
        string $targetType,
        ?int $targetId,
        string $status = 'posted',
        ?string $error = null
    ): LegacyLedgerPosting {
        return LegacyLedgerPosting::create([
            'batch_id' => $line->batch_id,
            'term_id' => $line->term_id,
            'line_id' => $line->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'hash' => $this->hashLine($line),
            'status' => $status,
            'error_message' => $error,
        ]);
    }
}

