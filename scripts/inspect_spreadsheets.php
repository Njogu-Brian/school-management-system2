<?php
/**
 * Inspect spreadsheet templates safely (local only).
 *
 * Usage:
 *   php scripts/inspect_spreadsheets.php "path1.xlsx" "path2.xls"
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$files = array_slice($argv, 1);
if (!$files) {
    fwrite(STDERR, "Provide one or more spreadsheet paths.\n");
    exit(2);
}

foreach ($files as $file) {
    echo "\n== " . basename($file) . " ==\n";
    if (!is_file($file)) {
        echo "missing: {$file}\n";
        continue;
    }

    try {
        $spreadsheet = IOFactory::load($file);
    } catch (Throwable $e) {
        echo "load_failed: " . get_class($e) . " " . $e->getMessage() . "\n";
        continue;
    }

    $sheetCount = $spreadsheet->getSheetCount();
    echo "sheets: {$sheetCount}\n";

    for ($i = 0; $i < min($sheetCount, 3); $i++) {
        $sheet = $spreadsheet->getSheet($i);
        $title = (string) $sheet->getTitle();
        echo "- sheet[{$i}]: {$title}\n";

        $highestCol = $sheet->getHighestColumn();
        $highestRow = (int) $sheet->getHighestRow();
        $maxRows = min(15, $highestRow);
        $range = "A1:{$highestCol}{$maxRows}";
        $rows = $sheet->rangeToArray($range, null, true, true, true);
        if (!$rows) {
            echo "  empty\n";
            continue;
        }

        $header = array_values((array) array_shift($rows));
        $header = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $header);
        echo "  header: " . json_encode($header, JSON_UNESCAPED_UNICODE) . "\n";

        $previewRows = array_slice($rows, 0, 3);
        foreach ($previewRows as $rIdx => $row) {
            $vals = array_values((array) $row);
            $vals = array_map(static function ($v) {
                if (is_string($v)) {
                    $v = trim($v);
                    $v = preg_replace('/\\s+/', ' ', $v);
                    if ($v === '') {
                        return null;
                    }
                    return $v;
                }
                return $v;
            }, $vals);
            echo "  row" . ($rIdx + 2) . ": " . json_encode($vals, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}

