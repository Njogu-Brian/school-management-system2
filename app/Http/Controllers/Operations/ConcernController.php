<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentConcern;
use App\Services\ExpoPushService;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConcernController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentConcern::with(['student.classroom', 'concernedStaff'])
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

        $concerns = $query->paginate(20)->withQueryString();
        $categories = StudentConcern::CATEGORIES;

        return view('operations.concerns.index', compact('concerns', 'categories'));
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
        $concern = StudentConcern::with(['student.classroom', 'concernedStaff', 'createdBy'])->findOrFail($id);

        return view('operations.concerns.show', compact('concern'));
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
                            ['type' => 'student_concern', 'concern_id' => $concern->id]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Concern push failed: '.$e->getMessage());
            }
        }
    }
}
