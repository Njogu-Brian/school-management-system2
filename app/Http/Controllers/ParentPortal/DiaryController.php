<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Academics\DiaryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiaryController extends Controller
{
    public function index()
    {
        $parent = Auth::user();

        $students = $parent->children()
            ->with(['classroom', 'diary.latestEntry.author'])
            ->orderBy('first_name')
            ->get();

        return view('parent.diaries.index', compact('students'));
    }

    public function show(Student $student)
    {
        $this->authorizeStudent($student);

        $diary = $student->diary()->firstOrCreate([]);

        $diary->load(['entries.author.staff', 'entries.author.parentProfile']);

        $entries = $diary->entries()
            ->with(['author.staff', 'author.parentProfile'])
            ->orderBy('created_at')
            ->get();

        $diary->entries()
            ->where('author_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('parent.diaries.show', compact('student', 'entries', 'diary'));
    }

    public function storeEntry(Student $student, Request $request)
    {
        $this->authorizeStudent($student);

        $data = $request->validate([
            'content' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);

        $diary = $student->diary()->firstOrCreate([]);

        $attachments = $this->storeAttachments($request);

        $diary->entries()->create([
            'author_id' => Auth::id(),
            'author_type' => 'parent',
            'content' => $data['content'],
            'attachments' => $attachments,
        ]);

        return back()->with('success', 'Diary entry submitted.');
    }

    protected function authorizeStudent(Student $student): void
    {
        $parent = Auth::user();

        if ($student->parent_id !== $parent->parent_id) {
            abort(403, 'You do not have access to this student.');
        }
    }

    protected function storeAttachments(Request $request): ?array
    {
        if (!$request->hasFile('attachments')) {
            return null;
        }

        $paths = [];
        foreach ($request->file('attachments') as $file) {
            $paths[] = $file->store('diary_entries', 'public');
        }

        return $paths;
    }
}

