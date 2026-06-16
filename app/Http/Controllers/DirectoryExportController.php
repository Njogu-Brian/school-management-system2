<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Services\DirectoryExportService;
use App\Services\PDFExportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DirectoryExportController extends Controller
{
  public function __construct(
    protected DirectoryExportService $exportService,
    protected PDFExportService $pdfService,
  ) {}

  public function exportStudents(Request $request)
  {
    return $this->export($request, 'students', 'Students');
  }

  public function exportStaff(Request $request)
  {
    return $this->export($request, 'staff', 'Staff');
  }

  protected function export(Request $request, string $type, string $title)
  {
    $request->validate([
      'format' => 'required|in:excel,pdf',
      'fields' => 'nullable|array',
      'fields.*' => 'string|max:64',
    ]);

    $fields = $this->exportService->resolveFields($type, (array) $request->input('fields', []));
    $labels = $this->exportService->fieldLabels($type, $fields);

    if ($type === 'staff') {
      $records = $this->exportService->staffQuery($request)->get();
      $rows = $this->exportService->buildStaffRows($records, $fields);
      $filenameBase = 'staff_export_' . now()->format('Ymd_His');
    } else {
      $records = $this->exportService->studentQuery($request)->get();
      $rows = $this->exportService->buildStudentRows($records, $fields);
      $filenameBase = 'students_export_' . now()->format('Ymd_His');
    }

    if ($request->input('format') === 'pdf') {
      return $this->pdfService->generatePDF('exports.directory_table', [
        'title' => $title . ' Export',
        'subtitle' => $this->exportService->filterSummary($type, $request),
        'headers' => $labels,
        'rows' => $rows,
        'recordCount' => count($rows),
      ], [
        'filename' => $filenameBase . '.pdf',
        'orientation' => count($fields) > 6 ? 'landscape' : 'portrait',
      ]);
    }

    return Excel::download(new ArrayExport($rows, $labels), $filenameBase . '.xlsx');
  }
}
