<?php

namespace App\Http\Controllers;

use App\Services\SmsBalanceMonitorService;
use App\Services\SystemAlertService;
use Illuminate\Http\Request;

class AdminAlertController extends Controller
{
    public function __construct(
        private SystemAlertService $alerts,
        private SmsBalanceMonitorService $smsBalance,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Super Admin'), 403);

        return response()->json([
            'success' => true,
            'data' => [
                'alerts' => $this->alerts->pendingAlertsFor($user, 50),
                'pending_count' => $this->alerts->pendingCountFor($user),
            ],
        ]);
    }

    public function smsBalance(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Super Admin'), 403);

        return response()->json([
            'success' => true,
            'data' => $this->smsBalance->statusSnapshot($request->boolean('refresh')),
        ]);
    }

    public function acknowledge(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Super Admin'), 403);

        if (! $this->alerts->acknowledge($user, $id)) {
            abort(404);
        }

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pending_count' => $this->alerts->pendingCountFor($user),
            ],
        ]);
    }

    public function inbox(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Super Admin'), 403);

        return view('admin.alerts.inbox', [
            'alerts' => $this->alerts->pendingAlertsFor($user, 100),
        ]);
    }
}
