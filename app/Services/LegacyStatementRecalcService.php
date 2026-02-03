<?php

namespace App\Services;

use App\Models\LegacyStatementLine;
use App\Models\LegacyStatementTerm;

class LegacyStatementRecalcService
{
    public function recalcTermRunningBalance(int $termId): void
    {
        $lines = LegacyStatementLine::where('term_id', $termId)
            ->orderBy('sequence_no')
            ->get();

        $running = 0;
        foreach ($lines as $line) {
            $dr = (float) ($line->amount_dr ?? 0);
            $cr = (float) ($line->amount_cr ?? 0);
            $running += ($dr - $cr);
            $line->running_balance = $running;
            $line->save();
        }
    }

    /**
     * Recalculate running balances for all transactions from the edited line onwards,
     * including all subsequent transactions in the same term and all subsequent terms for the same student.
     */
    public function recalcFromLine(LegacyStatementLine $line): void
    {
        $term = $line->term;
        if (!$term) {
            return;
        }

        $allTerms = LegacyStatementTerm::where('batch_id', $term->batch_id)
            ->where('admission_number', $term->admission_number)
            ->orderBy('academic_year')
            ->orderBy('term_number')
            ->get();

        $startingBalance = 0;
        $isFirstTerm = ($term->term_number == 1);
        $recalculateFromStartOfTerm = $isFirstTerm;

        if ($recalculateFromStartOfTerm) {
            $startingBalance = (float) ($term->starting_balance ?? 0);
        } else {
            $previousTerm = $allTerms->where('academic_year', $term->academic_year)
                ->where('term_number', $term->term_number - 1)
                ->first();

            if ($previousTerm) {
                $lastLineOfPreviousTerm = LegacyStatementLine::where('term_id', $previousTerm->id)
                    ->orderBy('sequence_no', 'desc')
                    ->first();

                if ($lastLineOfPreviousTerm && $lastLineOfPreviousTerm->running_balance !== null) {
                    $startingBalance = (float) $lastLineOfPreviousTerm->running_balance;
                } elseif ($previousTerm->ending_balance !== null) {
                    $startingBalance = (float) $previousTerm->ending_balance;
                }
            }
        }

        $currentRunningBalance = $startingBalance;
        $foundEditedLine = false;

        foreach ($allTerms as $t) {
            $termLines = LegacyStatementLine::where('term_id', $t->id)
                ->orderBy('sequence_no')
                ->get();

            if ($recalculateFromStartOfTerm && $t->id === $term->id) {
                foreach ($termLines as $l) {
                    $dr = (float) ($l->amount_dr ?? 0);
                    $cr = (float) ($l->amount_cr ?? 0);
                    $currentRunningBalance += ($dr - $cr);
                    $l->running_balance = $currentRunningBalance;
                    $l->save();
                }
            } else {
                foreach ($termLines as $l) {
                    if ($l->id === $line->id) {
                        $foundEditedLine = true;
                    }

                    if ($foundEditedLine) {
                        $dr = (float) ($l->amount_dr ?? 0);
                        $cr = (float) ($l->amount_cr ?? 0);
                        $currentRunningBalance += ($dr - $cr);
                        $l->running_balance = $currentRunningBalance;
                        $l->save();
                    } else {
                        $currentRunningBalance = (float) ($l->running_balance ?? $currentRunningBalance);
                    }
                }
            }

            if ($termLines->isNotEmpty()) {
                $lastLine = $termLines->last();
                $t->ending_balance = $lastLine->running_balance;
                $t->save();
            }
        }
    }
}
