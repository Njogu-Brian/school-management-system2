<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollExport;
use App\Models\PayrollPeriod;
use App\Services\Hr\PayrollExports\ImBankSalaryUploadExport;
use App\Services\Hr\PayrollExports\KraPayeCsvExport;
use App\Services\Hr\PayrollExports\MpesaPayoutCsvExport;
use App\Services\Hr\PayrollExports\NssfExport;
use App\Services\Hr\PayrollExports\ShifExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PayrollExportsController extends Controller
{
    public function imbank(Request $request, int $periodId)
    {
        return $this->downloadExport(
            $request,
            $periodId,
            'imbank_salary_upload',
            fn (PayrollPeriod $period) => app(ImBankSalaryUploadExport::class)->export($period),
        );
    }

    public function nssf(Request $request, int $periodId)
    {
        return $this->downloadExport(
            $request,
            $periodId,
            'nssf',
            fn (PayrollPeriod $period) => app(NssfExport::class)->export($period),
        );
    }

    public function shif(Request $request, int $periodId)
    {
        return $this->downloadExport(
            $request,
            $periodId,
            'shif',
            fn (PayrollPeriod $period) => app(ShifExport::class)->export($period),
        );
    }

    public function kraPaye(Request $request, int $periodId)
    {
        return $this->downloadExport(
            $request,
            $periodId,
            'kra_paye',
            fn (PayrollPeriod $period) => app(KraPayeCsvExport::class)->export($period),
        );
    }

    public function mpesa(Request $request, int $periodId)
    {
        return $this->downloadExport(
            $request,
            $periodId,
            'mpesa_payout',
            fn (PayrollPeriod $period) => app(MpesaPayoutCsvExport::class)->export($period),
        );
    }

    /**
     * @param  callable(PayrollPeriod): \App\Services\Hr\PayrollExports\ExportResult  $exporter
     */
    private function downloadExport(Request $request, int $periodId, string $type, callable $exporter): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $period = PayrollPeriod::with('records.staff')->findOrFail($periodId);

        try {
            $result = $exporter($period);

            $export = PayrollExport::create([
                'payroll_period_id' => $period->id,
                'export_type' => $type,
                'original_filename' => $result->filename,
                'storage_disk' => $result->disk,
                'storage_path' => $result->path,
                'sha256' => $result->sha256,
                'meta' => $result->meta,
                'created_by' => $request->user()?->id,
            ]);

            return Storage::disk($export->storage_disk)->download($export->storage_path, $export->original_filename);
        } catch (Throwable $e) {
            Log::error('Payroll export failed', [
                'period_id' => $periodId,
                'export_type' => $type,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('hr.payroll.periods.show', $periodId)
                ->with('error', 'Export failed: '.$e->getMessage());
        }
    }
}
