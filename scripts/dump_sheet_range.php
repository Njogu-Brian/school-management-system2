<?php
/**
 * Dump a rectangular range from a spreadsheet for template reverse-engineering.
 *
 * Usage:
 *   php scripts/dump_sheet_range.php "<file>" [sheetIndex] [startRow] [endRow] [startCol] [endCol]
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$file = $argv[1] ?? null;
if (!$file) {
    fwrite(STDERR, "Usage: php scripts/dump_sheet_range.php <file> [sheetIndex] [startRow] [endRow] [startCol] [endCol]\n");
    exit(2);
}

$sheetIndex = isset($argv[2]) ? (int) $argv[2] : 0;
$startRow = isset($argv[3]) ? (int) $argv[3] : 1;
$endRow = isset($argv[4]) ? (int) $argv[4] : ($startRow + 30);
$startCol = isset($argv[5]) ? strtoupper((string) $argv[5]) : 'A';
$endCol = isset($argv[6]) ? strtoupper((string) $argv[6]) : 'K';

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
} catch (Throwable $e) {
    fwrite(STDERR, "load_failed: " . get_class($e) . " " . $e->getMessage() . "\n");
    exit(1);
}

$sheet = $spreadsheet->getSheet($sheetIndex);
echo "sheet: {$sheetIndex} " . $sheet->getTitle() . "\n";
echo "range: {$startCol}{$startRow}:{$endCol}{$endRow}\n";

$range = "{$startCol}{$startRow}:{$endCol}{$endRow}";
$rows = $sheet->rangeToArray($range, null, true, true, true);

foreach ($rows as $r => $row) {
    $vals = [];
    foreach (range($startCol, $endCol) as $c) {
        $v = $row[$c] ?? null;
        if (is_string($v)) {
            $v = trim(preg_replace('/\\s+/', ' ', $v));
            $v = $v === '' ? null : $v;
        }
        $vals[$c] = $v;
    }
    echo str_pad((string) $r, 5, ' ', STR_PAD_LEFT) . " " . json_encode($vals, JSON_UNESCAPED_UNICODE) . "\n";
}

