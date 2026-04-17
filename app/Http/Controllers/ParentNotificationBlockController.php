<?php

namespace App\Http\Controllers;

use App\Models\ParentInfo;
use App\Models\Student;
use Illuminate\Http\Request;

class ParentNotificationBlockController extends Controller
{
    public function index(Request $request)
    {
        $q = ParentInfo::query()
            ->whereNotNull('school_notifications_muted_parent')
            ->with(['students' => function ($rel) {
                $rel->where('archive', 0)->with(['classroom']);
            }]);

        if ($request->filled('search')) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $request->search) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('father_name', 'like', $term)
                    ->orWhere('mother_name', 'like', $term)
                    ->orWhereHas('students', function ($s) use ($term) {
                        $s->where('archive', 0)->where(function ($s2) use ($term) {
                            $s2->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('admission_number', 'like', $term);
                        });
                    });
            });
        }

        $parents = $q->orderByDesc('updated_at')->paginate(25)->withQueryString();

        return view('communication.parent_notification_blocks.index', compact('parents'));
    }

    public function create()
    {
        return view('communication.parent_notification_blocks.form', [
            'parent' => null,
            'student' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'school_notifications_muted_parent' => 'required|in:father,mother',
        ]);

        $student = Student::with('parent')->findOrFail($validated['student_id']);
        if (! $student->parent) {
            return back()->withInput()->with('error', 'This student has no parent record linked.');
        }

        $mute = $validated['school_notifications_muted_parent'];

        ParentInfo::validateSchoolNotificationMute($mute, $student->parent->getAttributes());

        $student->parent->update(['school_notifications_muted_parent' => $mute]);

        return redirect()
            ->route('communication.parent-notification-blocks.index')
            ->with('success', 'School notification preference saved.');
    }

    public function edit(ParentInfo $parentInfo)
    {
        $parent = $parentInfo;
        $student = $parent->students()->where('archive', 0)->orderBy('admission_number')->first();

        return view('communication.parent_notification_blocks.form', [
            'parent' => $parent,
            'student' => $student,
        ]);
    }

    public function update(Request $request, ParentInfo $parentInfo)
    {
        $parent = $parentInfo;

        $validated = $request->validate([
            'school_notifications_muted_parent' => 'nullable|in:father,mother',
        ]);

        $mute = $validated['school_notifications_muted_parent'] ?? null;
        $mute = $mute === '' ? null : $mute;

        ParentInfo::validateSchoolNotificationMute($mute, $parent->getAttributes());

        $parent->update(['school_notifications_muted_parent' => $mute]);

        return redirect()
            ->route('communication.parent-notification-blocks.index')
            ->with('success', $mute
                ? 'School notification preference updated.'
                : 'Preference cleared: both parents will receive notifications where contacts exist.');
    }
}
