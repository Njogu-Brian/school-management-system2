<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\InventoryReceiptsReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReceiptsReportController extends Controller
{
    public function __construct(private InventoryReceiptsReportService $report)
    {
    }

    public function index(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $category = $request->input('category');
        $report = $this->report->build($from, $to, $category ?: null);

        return view('inventory.reports.receipts', [
            'report' => $report,
            'from' => $report['from']->toDateString(),
            'to' => $report['to']->toDateString(),
            'category' => $category,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $report = $this->report->build(
            $request->input('from'),
            $request->input('to'),
            $request->input('category') ?: null,
        );
        $filename = 'inventory-receipts-'.$report['from']->toDateString().'-to-'.$report['to']->toDateString().'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Item', 'Category', 'Unit', 'From learners', 'Other receipts', 'Total received', 'Current stock']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, [
                    $row['name'],
                    $row['category'],
                    $row['unit'],
                    $row['from_learners'],
                    $row['other_receipts'],
                    $row['total_received'],
                    $row['current_stock'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
