<?php

namespace App\Services;

use App\Models\{Student, Votehead, FeeStructure, FeeCharge, OptionalFee, InvoiceItem};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for posting fee charges to invoices
 *
 * Handles the preview and commit operations for posting mandatory fees,
 * optional fees, and transport fees (if applicable) to student invoices.
 */
class PostingService
{
    /**
     * Build a preview list of items to post based on filters.
     *
     * Collects all fee charges (from fee structures, optional fees, and transport)
     * that should be posted to invoices for the specified year, term, and filters.
     *
     * @param array $filters Filters: year (int), term (int), votehead_id?, class_id?, stream_id?, student_id?
     * @return Collection Collection of items ready to be posted
     */
    public static function preview(array $filters): Collection
    {
        $year = (int)$filters['year']; $term = (int)$filters['term'];

        $students = Student::query()
            ->when(!empty($filters['student_id']), fn($q) => $q->where('id', $filters['student_id']))
            ->when(!empty($filters['class_id']),  fn($q) => $q->where('classroom_id', $filters['class_id']))
            ->when(!empty($filters['stream_id']), fn($q) => $q->where('stream_id',    $filters['stream_id']))
            ->get();

        $out = collect();

        foreach ($students as $student) {
            // Mandatory by FeeStructure
            $structure = FeeStructure::with('charges.votehead')
                ->where('classroom_id', $student->classroom_id)
                ->where('year', $year)->first();

            if ($structure) {
                $charges = $structure->charges->where('term', $term);
                if (!empty($filters['votehead_id'])) {
                    $charges = $charges->where('votehead_id', (int)$filters['votehead_id']);
                }
                foreach ($charges as $charge) {
                    $vh = $charge->votehead;
                    if (!$vh || !$vh->is_mandatory) continue;
                    $out->push([
                        'origin'      => 'structure',
                        'student_id'  => $student->id,
                        'votehead_id' => $vh->id,
                        'amount'      => (float)$charge->amount,
                    ]);
                }
            }

            // Optional billed for that term/year
            $opt = OptionalFee::query()
                ->where('student_id', $student->id)
                ->where('year', $year)->where('term', $term)
                ->where('status', 'billed')
                ->when(!empty($filters['votehead_id']), fn($q) => $q->where('votehead_id', (int)$filters['votehead_id']))
                ->get();

            foreach ($opt as $o) {
                $out->push([
                    'origin'=>'optional',
                    'student_id'=>$student->id,
                    'votehead_id'=>$o->votehead_id,
                    'amount'=>(float)($o->amount ?? 0),
                ]);
            }

            // Transport fees: If students have transport assignments with associated fees,
            // add them here. Example implementation (uncomment and adjust if transport fees exist):
            // $transportAssignment = \App\Models\StudentAssignment::where('student_id', $student->id)->first();
            // if ($transportAssignment && $transportAssignment->route) {
            //     $transportVotehead = Votehead::where('name', 'like', '%transport%')->first();
            //     if ($transportVotehead && $transportAssignment->route->fee_amount) {
            //         $out->push([
            //             'origin' => 'transport',
            //             'student_id' => $student->id,
            //             'votehead_id' => $transportVotehead->id,
            //             'amount' => (float)$transportAssignment->route->fee_amount,
            //         ]);
            //     }
            // }
        }

        // De-duplicate (student+votehead) and sum amounts if both structure+optional hit same votehead
        return $out->groupBy(fn($r) => $r['student_id'].'-'.$r['votehead_id'])
            ->map(function($rows){
                $first = $rows->first();
                $first['amount'] = collect($rows)->sum('amount');
                $first['origin'] = $rows->pluck('origin')->unique()->join('+');
                return $first;
            })->values();
    }

    /**
     * Commit posting → create/update invoice_items (active if current, else pending with effective_date)
     *
     * Creates or updates invoice items for the provided rows. Items are marked as
     * 'active' if posting for the current term/year, otherwise 'pending' with an effective date.
     *
     * @param Collection $rows Collection of items to post (from preview())
     * @param int $year Academic year
     * @param int $term Term number (1, 2, or 3)
     * @param bool $activateNow If true, items are marked 'active', else 'pending'
     * @param string|null $effectiveDate Effective date for pending items (Y-m-d format)
     * @return int Number of items successfully posted
     */
    public static function commit(Collection $rows, int $year, int $term, bool $activateNow, ?string $effectiveDate = null): int
    {
        $count = 0;
        DB::transaction(function () use ($rows, $year, $term, $activateNow, $effectiveDate, &$count) {
            foreach ($rows as $row) {
                $invoice = \App\Services\InvoiceService::ensure($row['student_id'], $year, $term);

                $item = \App\Models\InvoiceItem::updateOrCreate(
                    ['invoice_id'=>$invoice->id, 'votehead_id'=>$row['votehead_id']],
                    [
                        'amount'=> (float)$row['amount'],
                        'status'=> $activateNow ? 'active' : 'pending',
                        'effective_date'=> $activateNow ? null : ($effectiveDate ?? null),
                        'source'=> $row['origin'] ?? 'structure',
                    ]
                );
                \App\Services\InvoiceService::recalc($invoice);
                $count++;
            }
        });
        return $count;
    }
}
