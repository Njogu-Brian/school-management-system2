<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hr\PayslipController;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;

class ApiPayslipController extends Controller
{
    public function download(Request $request, int $id)
    {
        PayrollRecord::findOrFail($id);

        return app(PayslipController::class)->download($id);
    }
}
