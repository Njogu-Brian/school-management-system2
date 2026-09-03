<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$row = DB::table('bank_statement_transactions')->where('id', 1199)->first();
echo json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n\n";

echo "same file:\n";
$same = DB::table('bank_statement_transactions')
    ->where('statement_file_path', $row->statement_file_path)
    ->whereDate('transaction_date', '2026-08-24')
    ->get(['id','reference_number','description','amount','statement_file_path']);
foreach ($same as $r) echo json_encode($r, JSON_UNESCAPED_UNICODE), "\n";

echo "\nfile exists: ";
$path = $row->statement_file_path;
echo $path, "\n";
foreach ([$path, storage_path('app/'.$path), '/var/www/erp/storage/app/'.$path] as $p) {
    echo $p, ' exists='.(is_file($p)?'yes':'no'), "\n";
}
