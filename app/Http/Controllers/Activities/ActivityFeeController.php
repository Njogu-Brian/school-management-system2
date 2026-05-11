<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Models\ActivityFeeAttendance;
use App\Models\OptionalFee;
use App\Models\Student;
use App\Models\Votehead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityFeeController extends Controller
{
    protected function currentYear(): int
    {
        return (int) setting('current_year', date('Y'));
    }

    protected function currentTerm(): int
    {
        return (int) setting('current_term', 1);
    }

    protected function resolveActivityVotehead(Votehead $votehead): Votehead
    {
        abort_unless($votehead->is_activity_fee && $votehead->is_active, 404);

        return $votehead;
    }

    /**
     * Students enrolled for this activity fee (billed optional fee) for year/term, scoped for teachers.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function rosterStudentIdsForVotehead(Votehead $votehead, int $year, int $term)
    {
        $q = OptionalFee::query()
            ->where('votehead_id', $votehead->id)
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->whereHas('student', function ($sq) {
                $sq->where('archive', 0);
            });

        $user = Auth::user();
        if (!$user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Supervisor'])) {
            $q->whereHas('student', function ($sq) use ($user) {
                $user->applyTeacherStudentFilter($sq);
            });
        }

        return $q->pluck('student_id')->unique()->values();
    }

    public function index()
    {
        $voteheads = Votehead::query()
            ->activityFees()
            ->orderBy('name')
            ->get();

        $year = $this->currentYear();
        $term = $this->currentTerm();

        $counts = OptionalFee::query()
            ->selectRaw('votehead_id, COUNT(DISTINCT student_id) as c')
            ->whereIn('votehead_id', $voteheads->pluck('id'))
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->groupBy('votehead_id')
            ->pluck('c', 'votehead_id');

        return view('activities.fees.index', compact('voteheads', 'year', 'term', 'counts'));
    }

    public function show(Request $request, Votehead $votehead)
    {
        $votehead = $this->resolveActivityVotehead($votehead);
        $year = (int) $request->get('year', $this->currentYear());
        $term = (int) $request->get('term', $this->currentTerm());

        $studentIds = $this->rosterStudentIdsForVotehead($votehead, $year, $term);
        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->with('classroom')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('activities.fees.show', compact('votehead', 'students', 'year', 'term'));
    }

    public function printRoster(Request $request, Votehead $votehead)
    {
        $votehead = $this->resolveActivityVotehead($votehead);
        $year = (int) $request->get('year', $this->currentYear());
        $term = (int) $request->get('term', $this->currentTerm());

        $studentIds = $this->rosterStudentIdsForVotehead($votehead, $year, $term);
        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->with('classroom')
            ->orderBy('classroom_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('activities.fees.roster_print', compact('votehead', 'students', 'year', 'term'));
    }

    public function attendance(Request $request, Votehead $votehead)
    {
        $votehead = $this->resolveActivityVotehead($votehead);
        $year = (int) $request->get('year', $this->currentYear());
        $term = (int) $request->get('term', $this->currentTerm());
        $date = $request->get('date', now()->toDateString());

        $studentIds = $this->rosterStudentIdsForVotehead($votehead, $year, $term);
        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->with('classroom')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $records = ActivityFeeAttendance::query()
            ->where('votehead_id', $votehead->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        return view('activities.fees.attendance', compact('votehead', 'students', 'year', 'term', 'date', 'records'));
    }

    public function attendanceStore(Request $request, Votehead $votehead)
    {
        $votehead = $this->resolveActivityVotehead($votehead);

        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'term' => 'required|integer|in:1,2,3',
            'date' => 'required|date',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $year = (int) $request->year;
        $term = (int) $request->term;
        $date = Carbon::parse($request->date)->toDateString();

        $allowedIds = $this->rosterStudentIdsForVotehead($votehead, $year, $term)->map(fn ($id) => (int) $id)->all();
        $studentIds = array_map('intval', $request->student_ids ?? []);
        $studentIds = array_values(array_intersect($studentIds, $allowedIds));

        $user = Auth::user();

        DB::transaction(function () use ($votehead, $date, $studentIds, $request, $user) {
            ActivityFeeAttendance::query()
                ->where('votehead_id', $votehead->id)
                ->whereDate('attendance_date', $date)
                ->whereNotIn('student_id', $studentIds)
                ->delete();

            $notes = $request->notes;

            foreach ($studentIds as $studentId) {
                ActivityFeeAttendance::query()->updateOrCreate(
                    [
                        'votehead_id' => $votehead->id,
                        'student_id' => $studentId,
                        'attendance_date' => $date,
                    ],
                    [
                        'notes' => $notes,
                        'marked_by' => $user->id,
                        'marked_at' => now(),
                    ]
                );
            }
        });

        return redirect()->route('activity-fees.attendance', [
            'votehead' => $votehead->id,
            'year' => $year,
            'term' => $term,
            'date' => $date,
        ])->with('success', 'Activity attendance saved.');
    }

    public function records(Request $request, Votehead $votehead)
    {
        $votehead = $this->resolveActivityVotehead($votehead);

        $query = ActivityFeeAttendance::query()
            ->with(['student.classroom', 'markedBy'])
            ->where('votehead_id', $votehead->id);

        $user = Auth::user();
        if (!$user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Supervisor'])) {
            $query->whereHas('student', function ($sq) use ($user) {
                $user->applyTeacherStudentFilter($sq);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        $records = $query->orderByDesc('attendance_date')->orderBy('student_id')->paginate(50)->withQueryString();

        return view('activities.fees.records', compact('votehead', 'records'));
    }
}
