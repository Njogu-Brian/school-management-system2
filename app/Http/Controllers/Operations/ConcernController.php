<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StudentConcern;
use App\Models\StudentConcernMessage;
use App\Services\ConcernRaiserPresenter;
use App\Services\ExpoPushService;
use App\Services\ParentAppNotifyService;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ConcernController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentConcern::with([
            'student.classroom',
            'student.parent',
            'concernedStaff',
            'raisedBy',
        ])->orderByDesc('created_at');

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

        $concerns = $query->paginate(20)->withQueryString();
        $categories = StudentConcern::CATEGORIES;
        $raiser = app(ConcernRaiserPresenter::class);

        return view('operations.concerns.index', compact('concerns', 'categories', 'raiser'));
    }

    public function create()
    {
        $staff = Staff::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get();
        $categories = StudentConcern::CATEGORIES;

        return view('operations.concerns.create', compact('staff', 'categories'));
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

        $concern = DB::transaction(function () use ($validated) {
            $concern = StudentConcern::create([
                'student_id' => $validated['student_id'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'status' => 'open',
                'raised_by_user_id' => auth()->id(),
                'created_by' => auth()->id(),
            ]);
            $concern->concernedStaff()->sync($validated['staff_ids']);

            return $concern->load('concernedStaff');
        });

        $this->notifyConcernedStaff($concern);

        return redirect()->route('operations.concerns.show', $concern->id)
            ->with('success', 'Concern raised and staff notified.');
    }

    public function show($id)
    {
        $concern = StudentConcern::with([
            'student.classroom',
            'student.parent',
            'concernedStaff',
            'createdBy',
            'raisedBy',
            'messages.user',
        ])->findOrFail($id);

        $raiser = app(ConcernRaiserPresenter::class)->present($concern);

        return view('operations.concerns.show', compact('concern', 'raiser'));
    }

    public function update(Request $request, $id)
    {
        $concern = StudentConcern::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);
        $concern->update($validated);

        return back()->with('success', 'Concern status updated.');
    }

    public function storeMessage(Request $request, $id)
    {
        $concern = StudentConcern::with(['raisedBy', 'student', 'concernedStaff'])->findOrFail($id);

        if ($concern->status === 'closed') {
            throw ValidationException::withMessages([
                'body' => ['This concern is closed and no longer accepts messages.'],
            ]);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'status' => 'nullable|in:open,in_progress,resolved,closed',
        ]);

        StudentConcernMessage::create([
            'student_concern_id' => $concern->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'is_staff' => true,
        ]);

        $status = $validated['status'] ?? null;
        if ($status) {
            $concern->status = $status;
        } elseif ($concern->status === 'open') {
            $concern->status = 'in_progress';
        }
        $concern->save();

        app(ParentAppNotifyService::class)->notifyConcernReply($concern, $validated['body']);

        return redirect()
            ->route('operations.concerns.show', $concern->id)
            ->with('success', 'Reply sent to the parent who raised this concern.');
    }

    protected function notifyConcernedStaff(StudentConcern $concern): void
    {
        $smsBody = 'A concern was raised regarding a student you are linked to. Please check the portal or app for details.';
        foreach ($concern->concernedStaff as $staff) {
            try {
                $phone = $staff->phone_number ?? $staff->phone ?? null;
                if ($phone) {
                    app(SMSService::class)->sendSMS($phone, $smsBody);
                }
            } catch (\Throwable $e) {
                Log::warning('Concern SMS failed: '.$e->getMessage());
            }
            try {
                $email = $staff->work_email ?? $staff->personal_email ?? $staff->email ?? $staff->user?->email;
                if ($email) {
                    Mail::raw(
                        "A concern was raised for a student you are linked to.\nCategory: {$concern->category}\nSign in to view details.\n",
                        fn ($m) => $m->to($email)->subject('Student concern raised')
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Concern email failed: '.$e->getMessage());
            }
            try {
                if ($staff->user_id) {
                    $tokens = DB::table('user_device_tokens')
                        ->where('user_id', (int) $staff->user_id)
                        ->pluck('token')->filter()->values()->all();
                    if ($tokens) {
                        app(ExpoPushService::class)->sendToTokens(
                            $tokens,
                            'Student concern raised',
                            'A '.$concern->category.' concern was logged. Open the app for details.',
                            [
                                'type' => 'student_concern',
                                'concern_id' => $concern->id,
                                'deep_link' => "royalkingsusers://concerns/{$concern->id}",
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Concern push failed: '.$e->getMessage());
            }
        }
    }
}
