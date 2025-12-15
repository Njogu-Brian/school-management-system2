<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\PublicShopLink;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class PublicShopLinkController extends Controller
{
    public function index(Request $request)
    {
        $query = PublicShopLink::with(['student', 'classroom']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('token', 'like', "%{$search}%")
                  ->orWhereHas('student', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $links = $query->latest()->paginate(30);

        ActivityLog::log('view', null, 'Viewed public shop links');

        return view('pos.public-links.index', compact('links'));
    }

    public function create()
    {
        $students = Student::where('status', 'active')->orderBy('first_name')->get();
        $classrooms = Classroom::orderBy('name')->get();

        return view('pos.public-links.create', compact('students', 'classrooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'student_id' => 'nullable|exists:students,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'access_type' => 'required|in:student,class,public',
            'show_requirements_only' => 'boolean',
            'allow_custom_items' => 'boolean',
            'expires_at' => 'nullable|date|after:today',
            'is_active' => 'boolean',
        ]);

        // Validate based on access type
        if ($validated['access_type'] === 'student' && !$validated['student_id']) {
            return redirect()->back()
                ->withErrors(['student_id' => 'Student is required for student access type.'])
                ->withInput();
        }

        if ($validated['access_type'] === 'class' && !$validated['classroom_id']) {
            return redirect()->back()
                ->withErrors(['classroom_id' => 'Classroom is required for class access type.'])
                ->withInput();
        }

        $link = PublicShopLink::create($validated);

        ActivityLog::log('create', $link, "Created public shop link: {$link->name}");

        return redirect()->route('pos.public-links.index')
            ->with('success', 'Public shop link created successfully. URL: ' . $link->getUrl());
    }

    public function edit(PublicShopLink $link)
    {
        $students = Student::where('status', 'active')->orderBy('first_name')->get();
        $classrooms = Classroom::orderBy('name')->get();

        return view('pos.public-links.edit', compact('link', 'students', 'classrooms'));
    }

    public function update(Request $request, PublicShopLink $link)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'student_id' => 'nullable|exists:students,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'access_type' => 'required|in:student,class,public',
            'show_requirements_only' => 'boolean',
            'allow_custom_items' => 'boolean',
            'expires_at' => 'nullable|date|after:today',
            'is_active' => 'boolean',
        ]);

        // Validate based on access type
        if ($validated['access_type'] === 'student' && !$validated['student_id']) {
            return redirect()->back()
                ->withErrors(['student_id' => 'Student is required for student access type.'])
                ->withInput();
        }

        if ($validated['access_type'] === 'class' && !$validated['classroom_id']) {
            return redirect()->back()
                ->withErrors(['classroom_id' => 'Classroom is required for class access type.'])
                ->withInput();
        }

        $link->update($validated);

        ActivityLog::log('update', $link, "Updated public shop link: {$link->name}");

        return redirect()->route('pos.public-links.index')
            ->with('success', 'Public shop link updated successfully.');
    }

    public function destroy(PublicShopLink $link)
    {
        $linkName = $link->name;
        $link->delete();

        ActivityLog::log('delete', null, "Deleted public shop link: {$linkName}");

        return redirect()->route('pos.public-links.index')
            ->with('success', 'Public shop link deleted successfully.');
    }

    public function regenerateToken(PublicShopLink $link)
    {
        $link->token = PublicShopLink::generateToken();
        $link->save();

        ActivityLog::log('update', $link, "Regenerated token for public shop link: {$link->name}");

        return redirect()->back()
            ->with('success', 'Token regenerated. New URL: ' . $link->getUrl());
    }
}



