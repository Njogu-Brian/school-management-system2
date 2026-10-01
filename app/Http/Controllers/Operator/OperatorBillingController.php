<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\SchoolRegistry;
use App\Models\SchoolSubscription;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperatorBillingController extends Controller
{
    public function index(): View
    {
        $period = request('period', now()->format('Y-m'));
        $subscriptions = SchoolSubscription::query()
            ->with('school')
            ->where('period', $period)
            ->orderBy('status')
            ->get();

        return view('operator.billing.index', compact('subscriptions', 'period'));
    }

    public function storePayment(Request $request, SchoolRegistry $school, TenantProvisioningService $provisioning): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:64'],
            'reference' => ['nullable', 'string', 'max:128'],
            'period' => ['nullable', 'string', 'max:7'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $provisioning->recordPayment(
            $school,
            (float) $data['amount'],
            $data['method'],
            $data['reference'] ?? null,
            $data['period'] ?? null,
            $request->user()?->id,
            $data['notes'] ?? null,
        );

        return back()->with('status', 'Payment recorded.');
    }
}
