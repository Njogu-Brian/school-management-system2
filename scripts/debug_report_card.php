<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$adm = $argv[1] ?? 'RKS616';
$rc = App\Models\Academics\ReportCard::query()
    ->whereHas('student', fn ($q) => $q->where('admission_number', $adm))
    ->latest()
    ->first();

if (! $rc) {
    echo "No report card for {$adm}\n";
    exit(1);
}

$dto = App\Services\ReportCardBatchService::build($rc->id);
echo 'id=' . $rc->id . PHP_EOL;
echo 'student=' . ($dto['student']['name'] ?? '') . PHP_EOL;
echo 'subjects=' . count($dto['subjects'] ?? []) . PHP_EOL;
if (! empty($dto['subjects'][0])) {
    echo 'first=' . ($dto['subjects'][0]['subject_name'] ?? '') . PHP_EOL;
    echo 'first_score=' . json_encode($dto['subjects'][0]['exams'][0]['score'] ?? null) . PHP_EOL;
}

$html = view('academics.report_cards.pdf', ['dto' => $dto, 'report_card' => $rc])->render();
$out = storage_path('app/debug_report_card.html');
file_put_contents($out, $html);
echo 'html_bytes=' . strlen($html) . PHP_EOL;
echo 'html_path=' . $out . PHP_EOL;
echo substr(strip_tags($html), 0, 200) . PHP_EOL;

$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('academics.report_cards.pdf', [
    'dto' => $dto,
    'report_card' => $rc,
])
    ->setPaper('A4', 'portrait')
    ->setOption('defaultFont', 'DejaVu Sans')
    ->setOption('isHtml5ParserEnabled', true)
    ->setOption('isRemoteEnabled', true);
$pdfPath = storage_path('app/debug_rc.pdf');
file_put_contents($pdfPath, $pdf->output());
echo 'pdf_bytes=' . filesize($pdfPath) . PHP_EOL;
echo 'pdf_path=' . $pdfPath . PHP_EOL;
echo 'filename=' . App\Services\ReportCardBatchService::pdfFilename($dto) . PHP_EOL;
exec('strings ' . escapeshellarg($pdfPath) . ' | grep -F Michelle | head -3', $grepOut, $grepCode);
echo 'pdf_has_michelle=' . (empty($grepOut) ? 'no' : implode(' | ', $grepOut)) . PHP_EOL;
