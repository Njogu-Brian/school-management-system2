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

class ApiConcernController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentConcern::with(['student.classroom', 'concernedStaff', 'createdBy'])
            ->orderByDesc('created_at');

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

    public function show(int $id)
    {
        $concern = StudentConcern::with(['student.classroom', 'concernedStaff', 'createdBy', 'raisedBy'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->format($concern)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'category' => 'required|in:'.implode(',', StudentConcern::CATEGORIES),
            'description' => 'required|string|max:5000',
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'integer|exists:staff,id',
        ]);

        $concern = DB::transaction(function () use ($validated, $request) {
            $concern = StudentConcern::create([
                'student_id' => $validated['student_id'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'status' => 'open',
                'raised_by_user_id' => $request->user()->id,
                'created_by' => $request->user()->id,
            ]);

            $concern->concernedStaff()->sync($validated['staff_ids']);

            return $concern->load(['student.classroom', 'concernedStaff', 'createdBy']);
        });

        $this->notifyConcernedStaff($concern);

        return response()->json([
            'success' => true,
            'message' => 'Concern raised.',
            'data' => $this->format($concern),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $concern = StudentConcern::findOrFail($id);
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
