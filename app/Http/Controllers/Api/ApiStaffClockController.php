<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiStaffClockController extends Controller
{
    private function geofenceConfig(): array
    {
        $latRaw = setting('school_geofence_latitude');
        $lngRaw = setting('school_geofence_longitude');
        $radius = (float) setting('school_geofence_radius_meters', '100');

        // Settings are stored as strings, so normalise before the is_configured check.
        // Treat whitespace-only or non-numeric values as "not configured" to avoid false positives.
        $lat = (is_string($latRaw) ? trim($latRaw) : $latRaw);
        $lng = (is_string($lngRaw) ? trim($lngRaw) : $lngRaw);

        $latValid = $lat !== null && $lat !== '' && is_numeric($lat);
        $lngValid = $lng !== null && $lng !== '' && is_numeric($lng);

        return [
            'latitude' => $latValid ? (float) $lat : null,
            'longitude' => $lngValid ? (float) $lng : null,
            'radius_meters' => $radius > 0 ? $radius : 100.0,
            'is_configured' => $latValid && $lngValid,
        ];
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function ensureTeacherLikeUser(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasTeacherLikeRole()) {
            return response()->json([
                'success' => false,
                'message' => 'Only teachers can use clock-in/out.',
            ], 403);
        }

        if (! $user->staff) {
            return response()->json([
                'success' => false,
                'message' => 'No staff profile is linked to this account.',
            ], 422);
        }

        return null;
    }

    public function geofence(Request $request)
    {
        $cfg = $this->geofenceConfig();
        $user = $request->user();
        $canManage = $user && $user->hasAnyRole(['Admin', 'Super Admin', 'admin', 'super admin']);

        return response()->json([
            'success' => true,
            'data' => array_merge($cfg, ['can_manage' => $canManage]),
        ]);
    }

    public function updateGeofence(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Admin', 'Super Admin', 'admin', 'super admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update school geofence coordinates.',
            ], 403);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'numeric', 'min:25', 'max:1000'],
        ]);

        setting_set('school_geofence_latitude', (string) $validated['latitude']);
        setting_set('school_geofence_longitude', (string) $validated['longitude']);
        setting_set('school_geofence_radius_meters', (string) ($validated['radius_meters'] ?? 100));

        return response()->json([
            'success' => true,
            'message' => 'School geofence updated.',
            'data' => $this->geofenceConfig(),
        ]);
    }

    public function today(Request $request)
    {
        if ($response = $this->ensureTeacherLikeUser($request)) {
            return $response;
        }

        $staffId = (int) $request->user()->staff->id;
        $date = Carbon::today()->toDateString();
        $record = StaffAttendance::where('staff_id', $staffId)->where('date', $date)->first();

        return response()->json([
            'success' => true,
            'data' => $record ? [
                'id' => $record->id,
                'date' => (string) $record->date,
                'status' => $record->status,
                'check_in_time' => $record->check_in_time ? Carbon::parse($record->check_in_time)->format('H:i:s') : null,
                'check_out_time' => $record->check_out_time ? Carbon::parse($record->check_out_time)->format('H:i:s') : null,
                'check_in_distance_meters' => $record->check_in_distance_meters,
                'check_out_distance_meters' => $record->check_out_distance_meters,
            ] : null,
        ]);
    }

    public function history(Request $request)
    {
        if ($response = $this->ensureTeacherLikeUser($request)) {
            return $response;
        }

        $staffId = (int) $request->user()->staff->id;
        $limit = (int) $request->input('limit', 14);
        $limit = max(1, min(60, $limit));

        $rows = StaffAttendance::where('staff_id', $staffId)
            ->orderByDesc('date')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(function (StaffAttendance $record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date ? Carbon::parse($record->date)->toDateString() : null,
                    'status' => $record->status,
                    'check_in_time' => $record->check_in_time ? Carbon::parse($record->check_in_time)->format('H:i:s') : null,
                    'check_out_time' => $record->check_out_time ? Carbon::parse($record->check_out_time)->format('H:i:s') : null,
                    'check_in_distance_meters' => $record->check_in_distance_meters,
                    'check_out_distance_meters' => $record->check_out_distance_meters,
                ];
            })->values(),
        ]);
    }

    public function clockIn(Request $request)
    {
        if ($response = $this->ensureTeacherLikeUser($request)) {
            return $response;
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cfg = $this->geofenceConfig();
        if (! $cfg['is_configured']) {
            return response()->json([
                'success' => false,
                'message' => 'School geofence has not been configured by admin.',
            ], 422);
        }

        $distance = $this->haversineMeters(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (float) $cfg['latitude'],
            (float) $cfg['longitude']
        );

        if ($distance > (float) $cfg['radius_meters']) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'You are %.1fm away from school. You must be within %.0fm to clock in.',
                    $distance,
                    $cfg['radius_meters']
                ),
                'data' => [
                    'distance_meters' => round($distance, 2),
                    'allowed_radius_meters' => $cfg['radius_meters'],
                ],
            ], 422);
        }

        $staffId = (int) $request->user()->staff->id;
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        $record = StaffAttendance::firstOrNew(['staff_id' => $staffId, 'date' => $today]);
        if ($record->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'You already clocked in today.',
            ], 422);
        }

        $record->status = $record->status ?: 'present';
        $record->check_in_time = $nowTime;
        $record->check_in_latitude = (float) $validated['latitude'];
        $record->check_in_longitude = (float) $validated['longitude'];
        $record->check_in_distance_meters = round($distance, 2);
        $record->check_in_accuracy_meters = isset($validated['accuracy_meters']) ? (float) $validated['accuracy_meters'] : null;
        $record->marked_by = $request->user()->id;
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Clock-in recorded successfully.',
            'data' => [
                'check_in_time' => $nowTime,
                'distance_meters' => round($distance, 2),
            ],
        ]);
    }

    public function clockOut(Request $request)
    {
        if ($response = $this->ensureTeacherLikeUser($request)) {
            return $response;
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cfg = $this->geofenceConfig();
        if (! $cfg['is_configured']) {
            return response()->json([
                'success' => false,
                'message' => 'School geofence has not been configured by admin.',
            ], 422);
        }

        $distance = $this->haversineMeters(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (float) $cfg['latitude'],
            (float) $cfg['longitude']
        );

        if ($distance > (float) $cfg['radius_meters']) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'You are %.1fm away from school. You must be within %.0fm to clock out.',
                    $distance,
                    $cfg['radius_meters']
                ),
                'data' => [
                    'distance_meters' => round($distance, 2),
                    'allowed_radius_meters' => $cfg['radius_meters'],
                ],
            ], 422);
        }

        $staffId = (int) $request->user()->staff->id;
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        $record = StaffAttendance::where('staff_id', $staffId)->where('date', $today)->first();
        if (! $record || ! $record->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Please clock in first before clocking out.',
            ], 422);
        }

        if ($record->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'You already clocked out today.',
            ], 422);
        }

        $record->check_out_time = $nowTime;
        $record->check_out_latitude = (float) $validated['latitude'];
        $record->check_out_longitude = (float) $validated['longitude'];
        $record->check_out_distance_meters = round($distance, 2);
        $record->check_out_accuracy_meters = isset($validated['accuracy_meters']) ? (float) $validated['accuracy_meters'] : null;
        $record->marked_by = $request->user()->id;
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Clock-out recorded successfully.',
            'data' => [
                'check_out_time' => $nowTime,
                'distance_meters' => round($distance, 2),
            ],
        ]);
    }
}
