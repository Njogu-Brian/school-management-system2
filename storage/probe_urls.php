<?php

require '/var/www/erp/vendor/autoload.php';
$app = require '/var/www/erp/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// A Term 3 invoice with a balance: exactly what the portal's "Next Term Fees" button links to.
$inv = DB::table('invoices')
    ->whereNull('deleted_at')
    ->whereNotNull('hashed_id')
    ->where('term_id', 9)
    ->where('balance', '>', 0)
    ->select('id', 'hashed_id', 'invoice_number', 'student_id')
    ->first();

echo 'INVOICE_HASH=' . ($inv->hashed_id ?? 'NONE') . PHP_EOL;
echo 'INVOICE_NUMBER=' . ($inv->invoice_number ?? 'NONE') . PHP_EOL;

// Family portal links: one with several children, one with a single child.
$multi = DB::table('family_report_portal_links as l')
    ->join('students as s', 's.family_id', '=', 'l.family_id')
    ->where('l.is_active', 1)
    ->whereNotNull('l.family_id')
    ->where('s.archive', 0)
    ->groupBy('l.id', 'l.token')
    ->havingRaw('COUNT(s.id) > 1')
    ->select('l.token', DB::raw('COUNT(s.id) as kids'))
    ->first();

$single = DB::table('family_report_portal_links')
    ->where('is_active', 1)
    ->orderByDesc('id')
    ->value('token');

echo 'PORTAL_MULTI=' . ($multi->token ?? 'NONE') . ' kids=' . ($multi->kids ?? 0) . PHP_EOL;
echo 'PORTAL_ANY=' . ($single ?? 'NONE') . PHP_EOL;
