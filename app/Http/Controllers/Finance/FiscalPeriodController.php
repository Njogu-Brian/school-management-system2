<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FiscalPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalPeriodController extends Controller
{
    public function index(): View
    {
        $periods = FiscalPeriod::with('closedByUser')->orderByDesc('start_date')->get();

        return view('finance.accounting.fiscal_periods.index', compact('periods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        FiscalPeriod::create(array_merge($validated, ['status' => FiscalPeriod::STATUS_OPEN]));

        return back()->with('success', 'Fiscal period created.');
    }

    public function close(FiscalPeriod $fiscalPeriod): RedirectResponse
    {
        if (! $fiscalPeriod->isOpen()) {
            return back()->with('error', 'Period is already closed.');
        }

        $fiscalPeriod->update([
            'status' => FiscalPeriod::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Fiscal period closed. New transactions should use the next open period.');
    }
}
