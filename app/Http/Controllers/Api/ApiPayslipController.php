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
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $record = PayrollRecord::findOrFail($id);

        $privileged = $user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Finance Officer', 'Accountant',
        ]);

        if (! $privileged) {
            $ownStaffId = $user->staff?->id;
            if (! $ownStaffId || (int) $record->staff_id !== (int) $ownStaffId) {
                abort(403, 'You can only download your own payslip.');
            }
        }

        return app(PayslipController::class)->download($id);
    }
}
