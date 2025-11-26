<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryCard;
use App\Models\Student;
use App\Services\LibraryService;
use Illuminate\Http\Request;

class LibraryCardController extends Controller
{
    protected LibraryService $libraryService;

    public function __construct(LibraryService $libraryService)
    {
        $this->libraryService = $libraryService;
    }

    public function index(Request $request)
    {
        $query = LibraryCard::with('student');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            })->orWhere('card_number', 'like', "%{$search}%");
        }

        $cards = $query->latest()->paginate(20)->withQueryString();

        return view('library.cards.index', compact('cards'));
    }

    public function create(Request $request)
    {
        $studentId = $request->get('student_id');
        $students = Student::orderBy('first_name')->get();

        return view('library.cards.create', compact('students', 'studentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id|unique:library_cards,student_id',
            'validity_months' => 'nullable|integer|min:1|max:60',
        ]);

        try {
            $student = Student::findOrFail($validated['student_id']);
            $card = $this->libraryService->issueCard(
                $student,
                $validated['validity_months'] ?? 12
            );

            return redirect()
                ->route('library.cards.show', $card)
                ->with('success', 'Library card issued successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(LibraryCard $card)
    {
        $card->load(['student', 'borrowings.bookCopy.book', 'activeBorrowings']);
        return view('library.cards.show', compact('card'));
    }

    public function renew(LibraryCard $card)
    {
        $card->update([
            'expiry_date' => now()->addMonths(12),
            'status' => 'active',
        ]);

        return back()->with('success', 'Library card renewed successfully.');
    }
}

