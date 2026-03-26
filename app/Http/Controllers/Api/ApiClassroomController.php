<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiClassroomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Classroom::query()->orderBy('name');

        // Teachers only see their assigned classes (+ senior teacher campus scope)
        if ($user && $user->hasTeacherLikeRole()) {
            $classIds = $user->getDashboardClassroomIds();
            if (! empty($classIds)) {
                $query->whereIn('id', $classIds);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        $classes = $query->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'level' => $c->level ?? null,
            'code' => $c->code ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $classes]);
    }

    public function streams(Request $request, $classId)
    {
        $user = $request->user();
        $query = Stream::where('classroom_id', $classId)->orderBy('name');

        // Teachers: verify access to this class and filter streams if needed
        if ($user && $user->hasTeacherLikeRole()) {
            if (! $user->canTeacherAccessClassroom((int) $classId)) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $streamIds = $user->getEffectiveStreamIds();
            if (!empty($streamIds)) {
                $query->whereIn('id', $streamIds);
            }
        }

        $streams = $query->get()->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'class_id' => $s->classroom_id,
        ]);

        return response()->json(['success' => true, 'data' => $streams]);
    }

    /**
     * Subjects available for homework in a class (teacher: own assignments; admin: all in class).
     */
    public function subjects(Request $request, $classId)
    {
        $classId = (int) $classId;
        $user = $request->user();

        if ($user && $user->hasTeacherLikeRole()) {
            if (! $user->canTeacherAccessClassroom($classId)) {
                return response()->json(['success' => true, 'data' => []]);
            }
        }

        $privileged = $user && $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $staffId = $user?->staff?->id;

        if ($staffId && $user->hasTeacherLikeRole() && ! $privileged && ! $user->isSeniorTeacherUser()) {
            $ids = DB::table('classroom_subjects')
                ->where('classroom_id', $classId)
                ->where('staff_id', $staffId)
                ->pluck('subject_id')
                ->unique()
                ->values()
                ->all();
            $subjects = $ids === []
                ? collect()
                : Subject::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'code']);
        } else {
            $ids = DB::table('classroom_subjects')->where('classroom_id', $classId)->pluck('subject_id')->unique()->values()->all();
            $subjects = $ids === []
                ? collect()
                : Subject::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'code']);
        }

        return response()->json([
            'success' => true,
            'data' => $subjects->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code ?? null,
            ])->values(),
        ]);
    }
}
