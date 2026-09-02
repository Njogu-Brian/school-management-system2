<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InventoryReceiptsReportService;
use App\Services\RequirementsFulfilmentReportService;
use Illuminate\Http\Request;

class ApiInventoryReportsController extends Controller
{
    public function requirements(Request $request, RequirementsFulfilmentReportService $service)
    {
        $report = $service->build(
            $request->filled('academic_year_id') ? (int) $request->academic_year_id : null,
            $request->filled('term_id') ? (int) $request->term_id : null,
            $request->filled('classroom_id') ? (int) $request->classroom_id : null,
            $request->filled('stream_id') ? (int) $request->stream_id : null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $report['year'] ? ['id' => $report['year']->id, 'year' => $report['year']->year] : null,
                'term' => $report['term'] ? ['id' => $report['term']->id, 'name' => $report['term']->name] : null,
                'summary' => $report['summary'],
                'complete' => $report['complete'],
                'partial' => $report['partial'],
                'none' => $report['none'],
            ],
        ]);
    }

    public function receipts(Request $request, InventoryReceiptsReportService $service)
    {
        $report = $service->build(
            $request->input('from'),
            $request->input('to'),
            $request->input('category') ?: null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $report['from']->toDateString(),
                'to' => $report['to']->toDateString(),
                'grand_total' => $report['grand_total'],
                'rows' => $report['rows'],
            ],
        ]);
    }
}
