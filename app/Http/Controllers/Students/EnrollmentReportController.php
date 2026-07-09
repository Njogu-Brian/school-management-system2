<?php

namespace App\Http\Controllers\Students;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Services\EnrollmentReportService;
use App\Services\PDFExportService;
use App\Support\AcademicContext;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentReportController extends Controller
{
  public function __construct(
    protected EnrollmentReportService $service,
    protected PDFExportService $pdfService,
  ) {
  }

  public function index(Request $request)
  {
    $context = AcademicContext::forView(
      $request->integer('academic_year_id') ?: null,
      $request->integer('term_id') ?: null,
    );

    $year = $this->resolveYearNumber($request, $context);
    $termNumber = $this->resolveTermNumber($request, $context);
    $campus = $this->resolveCampus($request);

    $report = $this->service->buildReport($year, $termNumber, $campus);

    return view('students.enrollment_report', [
      'rows' => $report['rows'],
      'totals' => $report['totals'],
      'year' => $report['year'],
      'termNumber' => $report['term'],
      'campus' => $campus,
      'context' => $context,
      'subtitle' => $this->service->subtitle($report['year'], $report['term'], $campus),
    ]);
  }

  public function exportExcel(Request $request)
  {
    $payload = $this->buildExportPayload($request);
    $filename = 'enrollment_by_class_'.now()->format('Ymd_His').'.xlsx';

    return Excel::download(
      new ArrayExport($payload['rows'], $payload['headings']),
      $filename,
    );
  }

  public function exportPdf(Request $request)
  {
    $payload = $this->buildExportPayload($request);

    return $this->pdfService->generatePDF('exports.enrollment_by_class', [
      'title' => 'Enrollment by Class',
      'subtitle' => $payload['subtitle'],
      'headers' => $payload['headings'],
      'rows' => $payload['rows'],
      'totals' => $payload['totalsRow'],
      'recordCount' => count($payload['report']['rows']),
    ], [
      'filename' => 'enrollment_by_class_'.now()->format('Ymd_His').'.pdf',
      'orientation' => 'portrait',
    ]);
  }

  /**
   * @return array{
   *   report: array,
   *   rows: list<list<int|string>>,
   *   totalsRow: list<int|string>,
   *   headings: list<string>,
   *   subtitle: string
   * }
   */
  protected function buildExportPayload(Request $request): array
  {
    $context = AcademicContext::forView(
      $request->integer('academic_year_id') ?: null,
      $request->integer('term_id') ?: null,
    );

    $year = $this->resolveYearNumber($request, $context);
    $termNumber = $this->resolveTermNumber($request, $context);
    $campus = $this->resolveCampus($request);
    $includeCampus = $campus === null;

    $report = $this->service->buildReport($year, $termNumber, $campus);
    $rows = $this->service->toExportRows($report['rows'], $includeCampus);
    $rows[] = $this->service->toExportTotalsRow($report['totals'], $includeCampus);

    return [
      'report' => $report,
      'rows' => $rows,
      'totalsRow' => $this->service->toExportTotalsRow($report['totals'], $includeCampus),
      'headings' => $this->service->exportHeadings($includeCampus),
      'subtitle' => $this->service->subtitle($report['year'], $report['term'], $campus),
    ];
  }

  protected function resolveYearNumber(Request $request, array $context): int
  {
    $selectedYearId = $context['selectedYearId'] ?? null;
    if ($selectedYearId) {
      $yearModel = collect($context['years'] ?? [])->firstWhere('id', $selectedYearId);
      if ($yearModel && ! empty($yearModel->year)) {
        return (int) $yearModel->year;
      }
    }

    return (int) (setting('current_year') ?? date('Y'));
  }

  protected function resolveTermNumber(Request $request, array $context): int
  {
    $selectedTermId = $context['selectedTermId'] ?? null;
    if ($selectedTermId) {
      $termModel = collect($context['terms'] ?? [])->firstWhere('id', $selectedTermId);
      if ($termModel && preg_match('/\d+/', (string) $termModel->name, $matches)) {
        return (int) $matches[0];
      }
    }

    return get_current_term_number() ?? 1;
  }

  protected function resolveCampus(Request $request): ?string
  {
    $campus = strtolower(trim((string) $request->input('campus', '')));

    return in_array($campus, ['lower', 'upper'], true) ? $campus : null;
  }
}
