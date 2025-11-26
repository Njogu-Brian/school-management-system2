<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\BookBorrowing;
use App\Models\BookCopy;
use App\Models\LibraryCard;
use App\Services\LibraryService;
use Illuminate\Http\Request;

class BookBorrowingController extends Controller
{
    protected LibraryService $libraryService;

    public function __construct(LibraryService $libraryService)
    {
        $this->libraryService = $libraryService;
    }

    public function index(Request $request)
    {
        $query = BookBorrowing::with(['student', 'bookCopy.book', 'libraryCard']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $borrowings = $query->latest('borrowed_date')->paginate(20)->withQueryString();

        return view('library.borrowings.index', compact('borrowings'));
    }

    public function create(Request $request)
    {
        $cardId = $request->get('card_id');
        $copyId = $request->get('copy_id');

        $cards = LibraryCard::where('status', 'active')->with('student')->get();
        $availableCopies = BookCopy::where('status', 'available')->with('book')->get();

        return view('library.borrowings.create', compact('cards', 'availableCopies', 'cardId', 'copyId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'library_card_id' => 'required|exists:library_cards,id',
            'book_copy_id' => 'required|exists:book_copies,id',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        try {
            $card = LibraryCard::findOrFail($validated['library_card_id']);
            $copy = BookCopy::findOrFail($validated['book_copy_id']);

            $borrowing = $this->libraryService->borrowBook(
                $copy,
                $card,
                $validated['days'] ?? null
            );

            return redirect()
                ->route('library.borrowings.show', $borrowing)
                ->with('success', 'Book borrowed successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(BookBorrowing $borrowing)
    {
        $borrowing->load(['student', 'bookCopy.book', 'libraryCard']);
        return view('library.borrowings.show', compact('borrowing'));
    }

    public function return(BookBorrowing $borrowing, Request $request)
    {
        $validated = $request->validate([
            'condition' => 'nullable|in:new,good,fair,poor',
        ]);

        try {
            $this->libraryService->returnBook($borrowing, $validated['condition'] ?? null);

            return redirect()
                ->route('library.borrowings.index')
                ->with('success', 'Book returned successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function renew(BookBorrowing $borrowing, Request $request)
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        try {
            $this->libraryService->renewBorrowing($borrowing, $validated['days'] ?? null);

            return back()->with('success', 'Book renewed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

