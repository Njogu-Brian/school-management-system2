<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BioTime\BioTimeEmployeeExport;
use App\Services\BioTime\BioTimeSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiBioTimeIngestController extends Controller
{
    public function store(Request $request, BioTimeSyncService $sync)
    {
        if (! $this->tokenMatches($request)) {
            return response()->json(['success' => false, 'message' => 'Invalid BioTime ingest token.'], 401);
        }

        $validated = $request->validate([
            'transactions' => 'required|array|min:1|max:1000',
            'transactions.*.id' => 'nullable|integer',
            'transactions.*.emp_code' => 'required|max:64',
            'transactions.*.punch_time' => 'required|string|max:40',
            'transactions.*.punch_state' => 'nullable',
            'transactions.*.terminal_sn' => 'nullable|string|max:64',
            'transactions.*.terminal_alias' => 'nullable|string|max:128',
        ]);

        $result = $sync->ingest($validated['transactions']);
        Log::info('BioTime ingest', $result);

        return response()->json([
            'success' => true,
            'message' => 'Punches recorded.',
            'data' => $result,
        ]);
    }

    public function health(Request $request)
    {
        if (! $this->tokenMatches($request)) {
            return response()->json(['success' => false, 'message' => 'Invalid BioTime ingest token.'], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'BioTime ingest is ready.',
        ]);
    }

    public function employees(Request $request, BioTimeEmployeeExport $export)
    {
        if (! $this->tokenMatches($request)) {
            return response()->json(['success' => false, 'message' => 'Invalid BioTime ingest token.'], 401);
        }

        $employees = $export->activeEmployees();

        return response()->json([
            'success' => true,
            'message' => 'Active staff for BioTime employee sync.',
            'data' => [
                'employees' => $employees,
                'count' => count($employees),
                'defaults' => [
                    'department_id' => (int) config('biotime.default_department_id', 1),
                    'area_ids' => config('biotime.default_area_ids', [1]),
                ],
            ],
        ]);
    }

    private function tokenMatches(Request $request): bool
    {
        $expected = (string) config('biotime.ingest_token');
        if ($expected === '') {
            return false;
        }
        $provided = (string) ($request->header('X-BioTime-Token') ?: $request->bearerToken() ?: '');

        return hash_equals($expected, $provided);
    }
}
