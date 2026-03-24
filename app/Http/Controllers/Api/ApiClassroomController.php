<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use Illuminate\Http\Request;

class ApiClassroomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Classroom::query()->orderBy('name');

        // Teachers only see their assigned classes
        if ($user && $user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $classIds = $user->getAssignedClassroomIds();
            if ($user->hasRole('Senior Teacher')) {
                $classIds = array_unique(array_merge($classIds, $user->getSupervisedClassroomIds()));
            }
            if (!empty($classIds)) {
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
        if ($user && $user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $hasAccess = in_array((int) $classId, $user->getAssignedClassroomIds(), true);
            if (!$hasAccess && $user->hasRole('Senior Teacher')) {
                $hasAccess = in_array((int) $classId, $user->getSupervisedClassroomIds(), true);
            }
            if (!$hasAccess) {
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
}
