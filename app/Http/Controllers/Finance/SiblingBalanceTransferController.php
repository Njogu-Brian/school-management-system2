<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\SiblingBalanceTransferService;
use Illuminate\Http\Request;

class SiblingBalanceTransferController extends Controller
{
    public function store(Request $request, SiblingBalanceTransferService $service)
    {
        $validated = $request->validate([
            'from_student_id' => 'required|integer|exists:students,id',
            'to_student_id' => 'required|integer|exists:students,id|different:from_student_id',
        ]);

        $from = Student::withArchived()->findOrFail((int) $validated['from_student_id']);
        $to = Student::withArchived()->findOrFail((int) $validated['to_student_id']);

        try {
            $result = $service->transferOutstandingBalance($from, $to, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Balance transferred successfully (Ksh ' . number_format((float) ($result['transferred_amount'] ?? 0), 2) . ').');
    }
}

