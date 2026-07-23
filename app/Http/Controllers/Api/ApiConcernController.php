<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StudentConcern;
use App\Services\ExpoPushService;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ApiConcernController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = StudentConcern::with(['student.classroom', 'concernedStaff', 'createdBy'])
            ->orderByDesc('created_at');

        $this->applyViewerScope($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = '%'.addcslashes((string) $request->search, '%_\\').'%';
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('admission_number', 'like', $search);
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($c) => $this->format($c))->values();

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

    public function show(Request $request, int $id)
    {
        $concern = StudentConcern::with(['student.classroom', 'concernedStaff', 'createdBy', 'raisedBy'])
            ->findOrFail($id);

        if (! $this->canViewConcern($request, $concern)) {
            abort(403, 'You do not have access to this concern.');
        }

        return response()->json(['success' => true, 'data' => $this->format($concern)]);
    }

    /**
     * Lightweight staff picker for raising concerns — any authenticated user may search.
     * Requires a search term so the full staff directory is never dumped.
     */
    public function staffOptions(Request $request)
    {
        if (! $request->user()) {
            abort(401);
        }

        $validated = $request->validate([
            'search' => 'required|string|min:2|max:100',
        ]);

        $search = '%'.addcslashes($validated['search'], '%_\\').'%';
        $rows = Staff::query()
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('middle_name', 'like', $search)
                    ->orWhere('staff_id', 'like', $search)
                    ->orWhere('work_email', 'like', $search);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'middle_name', 'staff_id', 'job_title', 'designation']);

        $data = $rows->map(fn (Staff $s) => [
            'id' => $s->id,
            'full_name' => $s->full_name,
            'employee_number' => $s->staff_id,
            'job_title' => $s->job_title ?? $s->designation,
        ])->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|integer|exists:students,id',
            'student_ids' => 'nullable|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'category' => 'required|in:'.implode(',', StudentConcern::CATEGORIES),
            'description' => 'required|string|max:5000',
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'integer|exists:staff,id',
        ]);

        $studentIds = collect($validated['student_ids'] ?? [])
            ->when(! empty($validated['student_id']), fn ($c) => $c->push((int) $validated['student_id']))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($studentIds === []) {
            throw ValidationException::withMessages([
                'student_ids' => ['Select at least one student.'],
            ]);
        }

        $concerns = DB::transaction(function () use ($validated, $request, $studentIds) {
            $created = [];
            foreach ($studentIds as $studentId) {
                $concern = StudentConcern::create([
                    'student_id' => $studentId,
                    'category' => $validated['category'],
                    'description' => $validated['description'],
                    'status' => 'open',
                    'raised_by_user_id' => $request->user()->id,
                    'created_by' => $request->user()->id,
                ]);
                $concern->concernedStaff()->sync($validated['staff_ids']);
                $created[] = $concern->load(['student.classroom', 'concernedStaff', 'createdBy']);
            }

            return $created;
        });

        foreach ($concerns as $concern) {
            $this->notifyConcernedStaff($concern);
        }

        $formatted = array_map(fn ($c) => $this->format($c), $concerns);
        $count = count($formatted);

        return response()->json([
            'success' => true,
            'message' => $count === 1 ? 'Concern raised.' : "{$count} concerns raised.",
            'data' => $count === 1 ? $formatted[0] : $formatted,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $concern = StudentConcern::findOrFail($id);

        if (! $this->canViewConcern($request, $concern)) {
            abort(403, 'You do not have access to this concern.');
        }

        $validated = $request->validate([
            'status' => 'sometimes|required|in:open,in_progress,resolved,closed',
            'description' => 'sometimes|required|string|max:5000',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'integer|exists:staff,id',
        ]);

        if (isset($validated['status'])) {
            $concern->status = $validated['status'];
        }
        if (isset($validated['description'])) {
            $concern->description = $validated['description'];
        }
        $concern->save();

        if (array_key_exists('staff_ids', $validated)) {
            $concern->concernedStaff()->sync($validated['staff_ids'] ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Concern updated.',
            'data' => $this->format($concern->fresh(['student.classroom', 'concernedStaff', 'createdBy'])),
        ]);
    }

    /**
     * Ops/admin see all (or optional staff_id filter).
     * Staff see concerns where they are tagged (or that they raised).
     * Parents see concerns about their children (or that they raised).
     */
    protected function applyViewerScope($query, Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $privileged = $user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Senior Teacher',
            'Finance Officer', 'Accountant',
        ]);

        if ($request->filled('staff_id')) {
            $staffId = (int) $request->input('staff_id');
            if (! $privileged && (int) ($user->staff?->id ?? 0) !== $staffId) {
                abort(403, 'You can only view concerns tagged to you.');
            }
            $query->whereHas('concernedStaff', fn ($q) => $q->where('staff.id', $staffId));

            return;
        }

        if ($privileged) {
            return;
        }

        if ($user->staff) {
            $staffId = (int) $user->staff->id;
            $query->where(function ($q) use ($user, $staffId) {
                $q->whereHas('concernedStaff', fn ($qq) => $qq->where('staff.id', $staffId))
                    ->orWhere('raised_by_user_id', $user->id);
            });

            return;
        }

        if ($user->parent_id) {
            $childIds = $user->accessibleStudentIds();
            $query->where(function ($q) use ($user, $childIds) {
                $q->where('raised_by_user_id', $user->id);
                if ($childIds !== []) {
                    $q->orWhereIn('student_id', $childIds);
                }
            });

            return;
        }

        $query->where('raised_by_user_id', $user->id);
    }

    protected function canViewConcern(Request $request, StudentConcern $concern): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Senior Teacher',
            'Finance Officer', 'Accountant',
        ])) {
            return true;
        }

        if ((int) $concern->raised_by_user_id === (int) $user->id) {
            return true;
        }

        if ($user->staff) {
            return $concern->concernedStaff()->where('staff.id', $user->staff->id)->exists();
        }

        if ($user->parent_id) {
            return in_array((int) $concern->student_id, $user->accessibleStudentIds(), true);
        }

        return false;
    }

    protected function notifyConcernedStaff(StudentConcern $concern): void
    {
        $smsBody = 'A concern was raised regarding a student you are linked to. Please check the portal or app for details.';
        $pushTitle = 'Student concern raised';
        $pushBody = 'A '.$concern->category.' concern was logged. Open the app for details.';
        $emailSubject = 'Student concern raised';

        foreach ($concern->concernedStaff as $staff) {
            try {
                $phone = $staff->phone_number ?? $staff->phone ?? null;
                if ($phone) {
                    app(SMSService::class)->sendSMS($phone, $smsBody);
                }
            } catch (\Throwable $e) {
                Log::warning('Concern SMS failed: '.$e->getMessage(), ['staff_id' => $staff->id]);
            }

            try {
                $email = $staff->work_email ?? $staff->personal_email ?? $staff->email ?? $staff->user?->email;
                if ($email) {
                    Mail::raw(
                        "A concern was raised for a student you are linked to.\n\nCategory: {$concern->category}\nPlease sign in to the portal or app to view details.\n",
                        function ($message) use ($email, $emailSubject) {
                            $message->to($email)->subject($emailSubject);
                        }
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Concern email failed: '.$e->getMessage(), ['staff_id' => $staff->id]);
            }

            try {
                $userId = $staff->user_id;
                if ($userId) {
                    $tokens = DB::table('user_device_tokens')
                        ->where('user_id', (int) $userId)
                        ->pluck('token')
                        ->filter(fn ($t) => is_string($t) && $t !== '')
                        ->values()
                        ->all();
                    if ($tokens) {
                        app(ExpoPushService::class)->sendToTokens(
                            $tokens,
                            $pushTitle,
                            $pushBody,
                            [
                                'type' => 'student_concern',
                                'concern_id' => $concern->id,
                                'student_id' => $concern->student_id,
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Concern push failed: '.$e->getMessage(), ['staff_id' => $staff->id]);
            }
        }
    }

    protected function format(StudentConcern $c): array
    {
        return [
            'id' => $c->id,
            'student_id' => $c->student_id,
            'student_name' => $c->student?->full_name,
            'admission_number' => $c->student?->admission_number,
            'class_name' => $c->student?->classroom?->name,
            'category' => $c->category,
            'description' => $c->description,
            'status' => $c->status,
            'staff' => $c->concernedStaff->map(fn (Staff $s) => [
                'id' => $s->id,
                'name' => $s->full_name ?? $s->name,
            ])->values()->all(),
            'created_by_name' => $c->createdBy?->name,
            'created_at' => optional($c->created_at)?->toIso8601String(),
            'updated_at' => optional($c->updated_at)?->toIso8601String(),
        ];
    }
}
