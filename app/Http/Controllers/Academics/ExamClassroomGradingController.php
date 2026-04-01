<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\GradingBand;
use App\Models\Academics\GradingScheme;
use App\Models\Academics\GradingSchemeMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamClassroomGradingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exams.view')->only(['index', 'edit', 'bulkForm', 'duplicateForm']);
        $this->middleware('permission:exams.edit')->only(['update', 'bulkApply', 'duplicateScheme']);
    }

    public function index()
    {
        $user = Auth::user();
        $privileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $isTeacher = ($user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher')) && ! $privileged;

        $classroomsQuery = Classroom::query()->orderBy('name');
        if ($isTeacher) {
            $ids = $user->getAssignedClassroomIds();
            if ($ids === []) {
                $classrooms = collect();
            } else {
                $classrooms = $classroomsQuery->whereIn('id', $ids)->get();
            }
        } else {
            $classrooms = $classroomsQuery->get();
        }

        $mappings = GradingSchemeMapping::query()
            ->whereIn('classroom_id', $classrooms->pluck('id'))
            ->with('scheme')
            ->get()
            ->keyBy('classroom_id');

        return view('academics.exams.grading.index', compact('classrooms', 'mappings'));
    }

    public function edit(Classroom $classroom)
    {
        $this->authorizeClassroom($classroom);

        $schemes = GradingScheme::orderBy('name')->get();
        $mapping = GradingSchemeMapping::where('classroom_id', $classroom->id)->first();
        $previewSchemeId = $mapping?->grading_scheme_id ?? GradingScheme::where('is_default', true)->value('id');
        $bands = $previewSchemeId
            ? GradingBand::where('grading_scheme_id', $previewSchemeId)->orderByDesc('min')->get()
            : collect();

        return view('academics.exams.grading.edit', compact('classroom', 'schemes', 'mapping', 'bands', 'previewSchemeId'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroom($classroom);

        $v = $request->validate([
            'grading_scheme_id' => 'nullable|exists:grading_schemes,id',
        ]);

        if (empty($v['grading_scheme_id'])) {
            GradingSchemeMapping::where('classroom_id', $classroom->id)->delete();
        } else {
            GradingSchemeMapping::updateOrCreate(
                ['classroom_id' => $classroom->id],
                ['grading_scheme_id' => (int) $v['grading_scheme_id']]
            );
        }

        return redirect()
            ->route('academics.exams.grading.index')
            ->with('success', 'Grading scheme updated for '.$classroom->name.'.');
    }

    public function bulkForm()
    {
        $user = Auth::user();
        $privileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $isTeacher = ($user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher')) && ! $privileged;

        $classroomsQuery = Classroom::query()->orderBy('name');
        if ($isTeacher) {
            $ids = $user->getAssignedClassroomIds();
            $classrooms = $ids === [] ? collect() : $classroomsQuery->whereIn('id', $ids)->get();
        } else {
            $classrooms = $classroomsQuery->get();
        }

        $schemes = GradingScheme::orderBy('name')->get();

        return view('academics.exams.grading.bulk', compact('classrooms', 'schemes'));
    }

    public function bulkApply(Request $request)
    {
        $v = $request->validate([
            'grading_scheme_id' => 'required|exists:grading_schemes,id',
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        foreach ($v['classroom_ids'] as $cid) {
            $classroom = Classroom::findOrFail((int) $cid);
            $this->authorizeClassroom($classroom);
            GradingSchemeMapping::updateOrCreate(
                ['classroom_id' => $classroom->id],
                ['grading_scheme_id' => (int) $v['grading_scheme_id']]
            );
        }

        return redirect()
            ->route('academics.exams.grading.index')
            ->with('success', 'Grading scheme applied to '.count($v['classroom_ids']).' class(es).');
    }

    public function duplicateForm()
    {
        $schemes = GradingScheme::with('bands')->orderBy('name')->get();

        $user = Auth::user();
        $privileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $isTeacher = ($user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher')) && ! $privileged;

        $classroomsQuery = Classroom::query()->orderBy('name');
        if ($isTeacher) {
            $ids = $user->getAssignedClassroomIds();
            $classrooms = $ids === [] ? collect() : $classroomsQuery->whereIn('id', $ids)->get();
        } else {
            $classrooms = $classroomsQuery->get();
        }

        return view('academics.exams.grading.duplicate', compact('schemes', 'classrooms'));
    }

    public function duplicateScheme(Request $request)
    {
        $v = $request->validate([
            'source_scheme_id' => 'required|exists:grading_schemes,id',
            'new_name' => 'required|string|max:255',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $newId = DB::transaction(function () use ($v) {
            $src = GradingScheme::with('bands')->findOrFail((int) $v['source_scheme_id']);
            $new = GradingScheme::create([
                'name' => $v['new_name'],
                'type' => $src->type,
                'meta' => $src->meta,
                'is_default' => false,
            ]);
            foreach ($src->bands as $b) {
                GradingBand::create([
                    'grading_scheme_id' => $new->id,
                    'min' => $b->min,
                    'max' => $b->max,
                    'label' => $b->label,
                    'descriptor' => $b->descriptor,
                    'rank' => $b->rank,
                ]);
            }

            return $new->id;
        });

        $assigned = 0;
        foreach ($v['classroom_ids'] ?? [] as $cid) {
            $classroom = Classroom::findOrFail((int) $cid);
            $this->authorizeClassroom($classroom);
            GradingSchemeMapping::updateOrCreate(
                ['classroom_id' => $classroom->id],
                ['grading_scheme_id' => $newId]
            );
            $assigned++;
        }

        $msg = 'Duplicated grading scheme.';
        if ($assigned > 0) {
            $msg .= ' Applied to '.$assigned.' class(es).';
        }

        return redirect()
            ->route('academics.exams.grading.index')
            ->with('success', $msg);
    }

    private function authorizeClassroom(Classroom $classroom): void
    {
        $user = Auth::user();
        $privileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        if ($privileged) {
            return;
        }
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $ids = array_map('intval', $user->getAssignedClassroomIds());
            if (! in_array((int) $classroom->id, $ids, true)) {
                abort(403);
            }
        }
    }
}
