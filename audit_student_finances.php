<?php

/**
 * READ-ONLY student finance audit script.
 *
 * Usage (on the server, from the project root):
 *   php audit_student_finances.php RKS577
 *   php audit_student_finances.php RKS577 --year=2026
 *
 * It prints a full reconciliation: invoices, items, payments, allocations,
 * credit/debit notes, legacy lines, archive events — and a list of anomalies.
 * It makes NO changes to the database.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ---------------------------------------------------------------
$adm = $argv[1] ?? null;
if (!$adm) {
    fwrite(STDERR, "Usage: php audit_student_finances.php <ADMISSION_NUMBER> [--year=YYYY]\n");
    exit(1);
}
$yearFilter = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--year=')) {
        $yearFilter = (int) substr($arg, 7);
    }
}

$hasTable = fn (string $t) => Schema::hasTable($t);
$hasCol = fn (string $t, string $c) => Schema::hasColumn($t, $c);

function row($label, $value) {
    echo str_pad($label, 34).": ".$value."\n";
}
function hr($title = '') {
    echo "\n".str_repeat('=', 90)."\n";
    if ($title !== '') echo $title."\n".str_repeat('=', 90)."\n";
}
function money($v) { return number_format((float) $v, 2); }
function dt($v) { return $v ? (string) $v : '-'; }

$anomalies = [];
$note = function (string $msg) use (&$anomalies) { $anomalies[] = $msg; };

// ---------------------------------------------------------------
hr("1. STUDENT RECORD");
$student = DB::table('students')->where('admission_number', $adm)->first();
if (!$student) {
    fwrite(STDERR, "Student {$adm} not found.\n");
    exit(1);
}
row('ID', $student->id);
row('Name', trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? '')));
row('Classroom / Stream', ($student->classroom_id ?? '-').' / '.($student->stream_id ?? '-'));
row('Status', $student->status ?? '-');
row('archive flag', $student->archive ?? '-');
foreach (['admission_date', 'enrollment_year', 'enrollment_term', 'archived_at', 'archived_reason', 'archived_notes', 'restored_at', 'restored_reason'] as $c) {
    if (Schema::hasColumn('students', $c)) row($c, dt($student->{$c} ?? null));
}
if (($student->archive ?? 0) == 1) {
    $note('Student is CURRENTLY archived (archive=1). Invoices/payments may have been soft-deleted by the archive cascade.');
}

// ---------------------------------------------------------------
hr("2. ARCHIVE / RESTORE HISTORY (archive_audits)");
if ($hasTable('archive_audits')) {
    $audits = DB::table('archive_audits')->where('student_id', $student->id)->orderBy('created_at')->get();
    if ($audits->isEmpty()) {
        echo "(no audit rows)\n";
    } else {
        foreach ($audits as $a) {
            echo sprintf("- [%s] %s | reason: %s | by user_id: %s\n",
                $a->created_at, strtoupper($a->action), $a->reason ?? '-', $a->actor_id ?? '-');
        }
    }
    $archiveEvents = $audits->where('action', 'archive')->count();
    $restoreEvents = $audits->where('action', 'restore')->count();
    if ($archiveEvents !== $restoreEvents && ($student->archive ?? 0) == 0) {
        $note("Audit imbalance: {$archiveEvents} archive vs {$restoreEvents} restore events but student is active.");
    }
} else {
    echo "(archive_audits table missing)\n";
}

// ---------------------------------------------------------------
hr("3. INVOICES (incl. soft-deleted & reversed)");
$invQuery = DB::table('invoices')->where('student_id', $student->id)->orderBy('year')->orderBy('term')->orderBy('id');
if ($yearFilter) $invQuery->where('year', $yearFilter);
$invoices = $invQuery->get();
if ($invoices->isEmpty()) {
    echo "(no invoices)\n";
}

$invoiceSummaries = [];
foreach ($invoices as $inv) {
    $items = DB::table('invoice_items')->where('invoice_id', $inv->id)->get();
    $activeItems = $items->where('status', 'active')->whereNull('deleted_at');
    $itemsTotal = $activeItems->sum('amount');

    $itemIds = $items->pluck('id')->all();
    $allocated = 0.0;
    if (!empty($itemIds) && $hasTable('payment_allocations')) {
        $allocated = (float) DB::table('payment_allocations')->whereIn('invoice_item_id', $itemIds)->sum('amount');
    }

    $flags = [];
    if (property_exists($inv, 'deleted_at') && $inv->deleted_at) $flags[] = 'SOFT-DELETED';
    if (property_exists($inv, 'reversed_at') && $inv->reversed_at) $flags[] = 'REVERSED@'.$inv->reversed_at;
    if (($inv->status ?? '') === 'reversed') $flags[] = 'status=reversed';

    echo sprintf(
        "INV #%s | %s | Year %s Term %s | total=%s paid=%s balance=%s | items_total=%s | allocated=%s | status=%s %s\n",
        $inv->id,
        $inv->invoice_number ?? '-',
        $inv->year ?? '?',
        $inv->term ?? '?',
        money($inv->total ?? 0),
        money($inv->paid_amount ?? 0),
        money($inv->balance ?? 0),
        money($itemsTotal),
        money($allocated),
        $inv->status ?? '-',
        $flags ? '['.implode(' ', $flags).']' : ''
    );
    echo "    created: ".dt($inv->created_at)."  updated: ".dt($inv->updated_at)."\n";

    foreach ($items as $it) {
        $vh = DB::table('voteheads')->where('id', $it->votehead_id)->value('name');
        $itAlloc = (!empty($it->id) && $hasTable('payment_allocations'))
            ? (float) DB::table('payment_allocations')->where('invoice_item_id', $it->id)->sum('amount') : 0.0;
        $cn = $hasTable('credit_notes') ? (float) DB::table('credit_notes')->where('invoice_item_id', $it->id)->sum('amount') : 0;
        $dn = $hasTable('debit_notes') ? (float) DB::table('debit_notes')->where('invoice_item_id', $it->id)->sum('amount') : 0;
        echo sprintf("      item #%s | %-28s | amount=%s | cn=%s | dn=%s | alloc=%s | status=%s | source=%s%s\n",
            $it->id, $vh ?? ('vh:'.$it->votehead_id), money($it->amount), money($cn), money($dn),
            money($itAlloc), $it->status ?? '-', $it->source ?? '-',
            (property_exists($it, 'deleted_at') && $it->deleted_at) ? ' [SOFT-DELETED]' : '');
    }

    // per-invoice consistency checks
    $isReversed = (property_exists($inv, 'reversed_at') && $inv->reversed_at) || ($inv->status ?? '') === 'reversed';
    $isDeleted = property_exists($inv, 'deleted_at') && $inv->deleted_at;
    if (!$isReversed && !$isDeleted) {
        if (abs(((float) $inv->total) - (float) $itemsTotal) > 0.05 && (float) $itemsTotal > 0) {
            $note("Invoice #{$inv->id} ({$inv->invoice_number}): invoice.total ".money($inv->total)." != sum(active items) ".money($itemsTotal).".");
        }
        if ($allocated > (float) $itemsTotal + 0.05) {
            $note("Invoice #{$inv->id}: allocations ".money($allocated)." EXCEED items total ".money($itemsTotal)." (over-allocated).");
        }
    }
    if ((int) ($inv->term ?? 0) === 2 && !$isReversed && !$isDeleted && $activeItems->sum('amount') > 0) {
        $note("TERM 2 invoice #{$inv->id} ({$inv->invoice_number}) is ACTIVE with items worth ".money($activeItems->sum('amount'))." — but student was away in Term 2. Candidate for reversal/credit.");
    }

    $invoiceSummaries[$inv->id] = [
        'year' => (int) ($inv->year ?? 0),
        'term' => (int) ($inv->term ?? 0),
        'items_total' => (float) $itemsTotal,
        'allocated' => $allocated,
        'reversed' => $isReversed,
        'deleted' => (bool) $isDeleted,
    ];
}

// ---------------------------------------------------------------
hr("4. PAYMENTS (incl. reversed & soft-deleted)");
$payQuery = DB::table('payments')->where('student_id', $student->id)->orderBy('payment_date')->orderBy('id');
$payments = $payQuery->get();
if ($payments->isEmpty()) {
    echo "(no payments)\n";
}

$totalPaidActive = 0.0; $totalReversed = 0.0; $totalUnallocated = 0.0; $allocToTerm2 = 0.0;
foreach ($payments as $p) {
    $allocs = $hasTable('payment_allocations')
        ? DB::table('payment_allocations')->where('payment_id', $p->id)->get() : collect();
    $allocSum = (float) $allocs->sum('amount');
    $unallocated = (float) $p->amount - $allocSum;

    $flags = [];
    if (($p->reversed ?? 0) == 1) $flags[] = 'REVERSED';
    if (property_exists($p, 'deleted_at') && $p->deleted_at) $flags[] = 'SOFT-DELETED';

    echo sprintf("PAY #%s | %s | amount=%s | allocated=%s | unallocated=%s | method=%s | ref=%s | receipt=%s %s\n",
        $p->id,
        dt($p->payment_date ?? $p->created_at),
        money($p->amount),
        money($allocSum),
        money(max(0, $unallocated)),
        $p->payment_method ?? '-',
        $p->reference ?? '-',
        $p->receipt_number ?? '-',
        $flags ? '['.implode(' ', $flags).']' : '');

    foreach ($allocs as $al) {
        $item = DB::table('invoice_items')->where('id', $al->invoice_item_id)->first();
        $invOfItem = $item ? DB::table('invoices')->where('id', $item->invoice_id)->first() : null;
        $vh = $item ? (DB::table('voteheads')->where('id', $item->votehead_id)->value('name')) : '?';
        echo sprintf("      -> alloc #%s | %s to item #%s (%s) on INV #%s (Year %s Term %s)%s%s\n",
            $al->id, money($al->amount), $al->invoice_item_id, $vh ?? '?',
            $invOfItem->id ?? '?', $invOfItem->year ?? '?', $invOfItem->term ?? '?',
            ($invOfItem && property_exists($invOfItem, 'deleted_at') && $invOfItem->deleted_at) ? ' [INV SOFT-DELETED]' : '',
            ($invOfItem && (property_exists($invOfItem, 'reversed_at') && $invOfItem->reversed_at)) ? ' [INV REVERSED]' : '');

        if ($invOfItem && (int) ($invOfItem->term ?? 0) === 2 && !($p->reversed ?? 0)) {
            $allocToTerm2 += (float) $al->amount;
        }
        if ($invOfItem && property_exists($invOfItem, 'reversed_at') && $invOfItem->reversed_at && !($p->reversed ?? 0)) {
            $note("Payment #{$p->id} has allocation #{$al->id} (".money($al->amount).") on REVERSED invoice #{$invOfItem->id} — orphaned allocation.");
        }
    }

    if (($p->reversed ?? 0) == 1) {
        $totalReversed += (float) $p->amount;
    } elseif (!(property_exists($p, 'deleted_at') && $p->deleted_at)) {
        $totalPaidActive += (float) $p->amount;
        if ($unallocated > 0.01) $totalUnallocated += $unallocated;
    }
}
row('Total payments (active)', money($totalPaidActive));
row('Total payments (reversed)', money($totalReversed));
row('Unallocated money', money($totalUnallocated));
if ($totalUnallocated > 0.01) {
    $note(money($totalUnallocated)." of payments is NOT allocated to any invoice item (sits as unallocated credit).");
}
if ($allocToTerm2 > 0.01) {
    $note(money($allocToTerm2)." of payments is allocated to TERM 2 invoice items — should likely be moved to Term 1/3 items.");
}

// ---------------------------------------------------------------
hr("5. CREDIT / DEBIT NOTES");
foreach (['credit_notes' => 'CREDIT', 'debit_notes' => 'DEBIT'] as $table => $label) {
    if (!$hasTable($table)) { echo "({$table} missing)\n"; continue; }
    $rows = DB::table($table)
        ->join('invoice_items', "{$table}.invoice_item_id", '=', 'invoice_items.id')
        ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
        ->where('invoices.student_id', $student->id)
        ->select("{$table}.*", 'invoices.year as inv_year', 'invoices.term as inv_term', 'invoices.id as inv_id')
        ->orderBy("{$table}.created_at")->get();
    if ($rows->isEmpty()) { echo "({$label}: none)\n"; continue; }
    foreach ($rows as $r) {
        echo sprintf("%s #%s | %s | %s | on INV #%s (Y%s T%s) | reason: %s\n",
            $label, $r->id, dt($r->created_at), money($r->amount ?? 0),
            $r->inv_id, $r->inv_year ?? '?', $r->inv_term ?? '?', $r->reason ?? '-');
    }
}

// ---------------------------------------------------------------
hr("6. LEGACY STATEMENT DATA (balance brought forward source)");
if ($hasTable('legacy_statement_terms')) {
    $terms = DB::table('legacy_statement_terms')->where('student_id', $student->id)
        ->orderBy('academic_year')->orderBy('term_number')->get();
    foreach ($terms as $t) {
        echo sprintf("LEGACY TERM | Y%s T%s (%s) | start=%s end=%s | status=%s\n",
            $t->academic_year, $t->term_number, $t->term_name ?? '-',
            money($t->starting_balance ?? 0), money($t->ending_balance ?? 0), $t->status ?? '-');
        $lines = DB::table('legacy_statement_lines')->where('term_id', $t->id)->orderBy('sequence_no')->get();
        foreach ($lines as $l) {
            echo sprintf("      %s | %-40s | DR %s | CR %s | bal %s\n",
                dt($l->txn_date), substr($l->narration_raw ?? '', 0, 40),
                money($l->amount_dr ?? 0), money($l->amount_cr ?? 0), money($l->running_balance ?? 0));
        }
    }
    if ($terms->isEmpty()) echo "(no legacy terms)\n";
} else {
    echo "(legacy tables missing)\n";
}

// ---------------------------------------------------------------
hr("7. RECONCILIATION PER TERM (active, non-reversed invoices)");
$perTerm = [];
foreach ($invoiceSummaries as $invId => $s) {
    if ($s['reversed'] || $s['deleted']) continue;
    $key = "Y{$s['year']} T{$s['term']}";
    $perTerm[$key]['invoiced'] = ($perTerm[$key]['invoiced'] ?? 0) + $s['items_total'];
    $perTerm[$key]['allocated'] = ($perTerm[$key]['allocated'] ?? 0) + $s['allocated'];
}
ksort($perTerm);
foreach ($perTerm as $k => $v) {
    echo sprintf("%-10s invoiced=%-12s allocated=%-12s open=%s\n",
        $k, money($v['invoiced']), money($v['allocated']), money($v['invoiced'] - $v['allocated']));
}
$sumInvoiced = array_sum(array_column($perTerm, 'invoiced'));
$sumAllocated = array_sum(array_column($perTerm, 'allocated'));
echo str_repeat('-', 60)."\n";
echo sprintf("TOTAL      invoiced=%-12s allocated=%-12s open=%s\n", money($sumInvoiced), money($sumAllocated), money($sumInvoiced - $sumAllocated));
echo sprintf("Payments:  active=%s reversed=%s unallocated=%s\n", money($totalPaidActive), money($totalReversed), money($totalUnallocated));
$effectivePaid = $sumAllocated + $totalUnallocated;
if (abs($effectivePaid - $totalPaidActive) > 0.05) {
    $note("Allocated + unallocated (".money($effectivePaid).") != total active payments (".money($totalPaidActive)."). Some allocations may point to reversed/deleted invoices or other students.");
}

// ---------------------------------------------------------------
hr("8. ANOMALIES / LIKELY ISSUES");
if (empty($anomalies)) {
    echo "(none detected)\n";
} else {
    foreach ($anomalies as $i => $a) {
        echo sprintf("%2d. %s\n", $i + 1, $a);
    }
}
echo "\nDone. (Read-only; nothing was changed.)\n";
