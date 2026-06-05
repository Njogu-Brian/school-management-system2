<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiVisitorsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $query = VisitorLog::with(['hostStaff'])->orderByDesc('checked_in_at');

        if ($request->boolean('on_site')) {
            $query->whereNull('checked_out_at');
        }

        if ($request->filled('date')) {
            $query->whereDate('checked_in_at', $request->string('date'));
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (VisitorLog $v) => $this->serialize($v))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:100',
            'organization' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:500',
            'host_name' => 'nullable|string|max:255',
            'host_staff_id' => 'nullable|exists:staff,id',
            'badge_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $visitor = VisitorLog::create([
            ...$validated,
            'checked_in_at' => now(),
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor checked in.',
            'data' => $this->serialize($visitor->load('hostStaff')),
        ], 201);
    }

    public function checkout(int $id)
    {
        $visitor = VisitorLog::findOrFail($id);
        if ($visitor->checked_out_at) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor already checked out.',
            ], 422);
        }

        $visitor->update(['checked_out_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor checked out.',
            'data' => $this->serialize($visitor->fresh('hostStaff')),
        ]);
    }

    protected function serialize(VisitorLog $v): array
    {
        return [
            'id' => $v->id,
            'visitor_name' => $v->visitor_name,
            'phone' => $v->phone,
            'organization' => $v->organization,
            'purpose' => $v->purpose,
            'host_name' => $v->host_name ?? $v->hostStaff?->full_name,
            'badge_number' => $v->badge_number,
            'checked_in_at' => $v->checked_in_at?->toIso8601String(),
            'checked_out_at' => $v->checked_out_at?->toIso8601String(),
            'on_site' => $v->isOnSite(),
            'notes' => $v->notes,
        ];
    }
}
