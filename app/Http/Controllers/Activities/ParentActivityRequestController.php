<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Models\ParentActivityChangeRequest;
use App\Services\ParentCoCurricularService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ParentActivityRequestController extends Controller
{
    public function __construct(protected ParentCoCurricularService $coCurricular) {}

    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'cancelled', 'all'], true)) {
            $status = 'pending';
        }

        $query = ParentActivityChangeRequest::query()
            ->with(['student.classroom', 'votehead', 'requestedBy', 'reviewedBy'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(40)->withQueryString();
        $pendingCount = ParentActivityChangeRequest::query()->pending()->count();

        return view('activities.parent_requests.index', compact('requests', 'status', 'pendingCount'));
    }

    public function approve(Request $request, ParentActivityChangeRequest $parentRequest)
    {
        $request->validate(['review_note' => 'nullable|string|max:1000']);

        try {
            $this->coCurricular->approve($parentRequest, $request->user(), $request->input('review_note'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Change confirmed. The parent has been notified.');
    }

    public function reject(Request $request, ParentActivityChangeRequest $parentRequest)
    {
        $request->validate(['review_note' => 'nullable|string|max:1000']);

        try {
            $this->coCurricular->reject($parentRequest, $request->user(), $request->input('review_note'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Request declined. The parent has been notified.');
    }
}
