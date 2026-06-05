<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\TrainingRecord;
use Illuminate\Http\Request;

class ApiStaffTrainingController extends Controller
{
    public function index(Request $request, int $staffId)
    {
        Staff::findOrFail($staffId);
        $perPage = min((int) $request->input('per_page', 20), 100);

        $paginated = TrainingRecord::query()
            ->where('staff_id', $staffId)
            ->orderByDesc('start_date')
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (TrainingRecord $r) => $this->serialize($r))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'staff_id' => $staffId,
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function show(int $staffId, int $id)
    {
        $record = TrainingRecord::query()
            ->where('staff_id', $staffId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($record, true),
        ]);
    }

    protected function serialize(TrainingRecord $r, bool $detailed = false): array
    {
        $payload = [
            'id' => $r->id,
            'staff_id' => $r->staff_id,
            'training_name' => $r->training_name,
            'provider' => $r->provider,
            'location' => $r->location,
            'start_date' => $r->start_date?->format('Y-m-d'),
            'end_date' => $r->end_date?->format('Y-m-d'),
            'duration_hours' => $r->duration_hours,
            'training_type' => $r->training_type,
            'status' => $r->status,
            'certificate_number' => $r->certificate_number,
            'cost' => $r->cost !== null ? (float) $r->cost : null,
        ];

        if ($detailed) {
            $payload += [
                'description' => $r->description,
                'objectives' => $r->objectives,
                'outcomes' => $r->outcomes,
                'certificate_file' => $r->certificate_file,
            ];
        }

        return $payload;
    }
}
