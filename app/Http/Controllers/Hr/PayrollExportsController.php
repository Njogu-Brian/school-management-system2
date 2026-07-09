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
use Illuminate\Support\Facades\Storage;

class PayrollExportsController extends Controller
{
    public function imbank(Request $request, int $periodId)
    {
        $period = PayrollPeriod::with('records.staff')->findOrFail($periodId);

        $result = app(ImBankSalaryUploadExport::class)->export($period);
        $export = PayrollExport::create([
            'payroll_period_id' => $period->id,
            'export_type' => 'imbank_salary_upload',
            'original_filename' => $result->filename,
            'storage_disk' => $result->disk,
            'storage_path' => $result->path,
            'sha256' => $result->sha256,
            'meta' => $result->meta,
            'created_by' => $request->user()?->id,
        ]);

        return Storage::disk($export->storage_disk)->download($export->storage_path, $export->original_filename);
    }

    public function nssf(Request $request, int $periodId)
    {
        $period = PayrollPeriod::with('records.staff')->findOrFail($periodId);

        $result = app(NssfExport::class)->export($period);
        $export = PayrollExport::create([
            'payroll_period_id' => $period->id,
            'export_type' => 'nssf',
            'original_filename' => $result->filename,
            'storage_disk' => $result->disk,
            'storage_path' => $result->path,
            'sha256' => $result->sha256,
            'meta' => $result->meta,
            'created_by' => $request->user()?->id,
        ]);

        return Storage::disk($export->storage_disk)->download($export->storage_path, $export->original_filename);
    }

    public function shif(Request $request, int $periodId)
    {
        $period = PayrollPeriod::with('records.staff')->findOrFail($periodId);

        $result = app(ShifExport::class)->export($period);
        $export = PayrollExport::create([
            'payroll_period_id' => $period->id,
            'export_type' => 'shif',
            'original_filename' => $result->filename,
            'storage_disk' => $result->disk,
            'storage_path' => $result->path,
            'sha256' => $result->sha256,
            'meta' => $result->meta,
            'created_by' => $request->user()?->id,
        ]);

        return Storage::disk($export->storage_disk)->download($export->storage_path, $export->original_filename);
    }

    public function kraPaye(Request $request, int $periodId)
    {
        $period = PayrollPeriod::with('records.staff')->findOrFail($periodId);

        $result = app(KraPayeCsvExport::class)->export($period);
        $export = PayrollExport::create([
            'payroll_period_id' => $period->id,
            'export_type' => 'kra_paye',
            'original_filename' => $result->filename,
            'storage_disk' => $result->disk,
            'storage_path' => $result->path,
            'sha256' => $result->sha256,
            'meta' => $result->meta,
            'created_by' => $request->user()?->id,
        ]);

        return Storage::disk($export->storage_disk)->download($export->storage_path, $export->original_filename);
    }

    public function mpesa(Request $request, int $periodId)
    {
        $period = PayrollPeriod::with('records.staff')->findOrFail($periodId);

        $result = app(MpesaPayoutCsvExport::class)->export($period);
        $export = PayrollExport::create([
            'payroll_period_id' => $period->id,
            'export_type' => 'mpesa_payout',
            'original_filename' => $result->filename,
            'storage_disk' => $result->disk,
            'storage_path' => $result->path,
            'sha256' => $result->sha256,
            'meta' => $result->meta,
            'created_by' => $request->user()?->id,
        ]);

        return Storage::disk($export->storage_disk)->download($export->storage_path, $export->original_filename);
    }
}

