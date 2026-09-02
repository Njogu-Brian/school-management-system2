<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\RequirementsFulfilmentReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequirementsReportController extends Controller
{
    public function __construct(private RequirementsFulfilmentReportService $report)
    {
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $report = $this->report->build(
            $filters['academic_year_id'],
            $filters['term_id'],
            $filters['classroom_id'],
            $filters['stream_id'],
        );

        $academicYears = \App\Support\AcademicContext::years();
        $terms = \App\Support\AcademicContext::allTermsForSelect();

        return view('inventory.reports.requirements', compact('report', 'academicYears', 'terms') + $filters);
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $report = $this->report->build(
            $filters['academic_year_id'],
            $filters['term_id'],
            $filters['classroom_id'],
            $filters['stream_id'],
        );
        $rows = $this->report->csvRows($report);
        $filename = 'requirements-fulfilment-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{academic_year_id:?int,term_id:?int,classroom_id:?int,stream_id:?int}
     */
    private function filters(Request $request): array
    {
        return [
            'academic_year_id' => $request->filled('academic_year_id') ? (int) $request->academic_year_id : null,
            'term_id' => $request->filled('term_id') ? (int) $request->term_id : null,
            'classroom_id' => $request->filled('classroom_id') ? (int) $request->classroom_id : null,
            'stream_id' => $request->filled('stream_id') ? (int) $request->stream_id : null,
        ];
    }
}
