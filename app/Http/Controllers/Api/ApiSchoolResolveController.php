<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public control-plane endpoint: school code → tenant API URL + branding snapshot.
 */
class ApiSchoolResolveController extends Controller
{
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:32'],
        ]);

        $code = SchoolRegistry::normalizeCode($validated['code']);
        $school = SchoolRegistry::query()->where('code', $code)->first();

        if (! $school) {
            return response()->json([
                'message' => 'School code not found. Check the code from your school and try again.',
            ], 404);
        }

        if (! $school->isActive()) {
            return response()->json([
                'message' => 'This school is not currently active. Contact your school administrator.',
                'status' => $school->status,
            ], 403);
        }

        return response()->json($school->toResolvePayload());
    }
}
