<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookBorrowing;
use App\Models\BookCopy;
use App\Models\LibraryCard;
use App\Models\Student;
use App\Services\LibraryService;
use Illuminate\Http\Request;

class ApiLibraryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = Book::query();

        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('author', 'like', $search)
                    ->orWhere('isbn', 'like', $search);
            });
        }

        if ($request->boolean('available_only')) {
            $query->where('available_copies', '>', 0);
        }

        $paginated = $query->orderBy('title')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title ?? '',
            'author' => $b->author ?? null,
            'isbn' => $b->isbn ?? null,
            'category' => $b->category ?? null,
            'total_copies' => (int) ($b->total_copies ?? 0),
            'available_copies' => (int) ($b->available_copies ?? 0),
            'status' => ($b->available_copies ?? 0) > 0 ? 'available' : 'unavailable',
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function borrowings(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $query = BookBorrowing::with(['student', 'bookCopy.book', 'libraryCard'])
            ->orderByDesc('borrowed_date')
            ->orderByDesc('id');

        $status = (string) $request->input('status', '');
        if ($status === 'overdue') {
            $query->where('status', 'borrowed')->whereDate('due_date', '<', now());
        } elseif ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->input('student_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($s) use ($search) {
                    $s->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('admission_number', 'like', $search);
                })->orWhereHas('bookCopy.book', fn ($b) => $b->where('title', 'like', $search));
            });
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (BookBorrowing $b) => $this->serializeBorrowing($b))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function issue(Request $request, LibraryService $libraryService)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'book_id' => 'required|exists:books,id',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $card = LibraryCard::where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        if (! $card) {
            try {
                $card = $libraryService->issueCard($student);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        $copy = BookCopy::where('book_id', $validated['book_id'])
            ->where('status', 'available')
            ->orderBy('copy_number')
            ->first();

        if (! $copy) {
            return response()->json([
                'success' => false,
                'message' => 'No available copies of this book.',
            ], 422);
        }

        try {
            $borrowing = $libraryService->borrowBook($copy, $card, $validated['days'] ?? null);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book issued.',
            'data' => $this->serializeBorrowing($borrowing->load(['student', 'bookCopy.book', 'libraryCard'])),
        ], 201);
    }

    public function returnBorrowing(Request $request, int $id, LibraryService $libraryService)
    {
        $borrowing = BookBorrowing::with(['bookCopy.book', 'libraryCard', 'student'])->findOrFail($id);

        $validated = $request->validate([
            'condition' => 'nullable|in:new,good,fair,poor',
        ]);

        if ($borrowing->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'Only active borrowings can be returned.',
            ], 422);
        }

        try {
            $libraryService->returnBook($borrowing, $validated['condition'] ?? null);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book returned.',
            'data' => $this->serializeBorrowing($borrowing->fresh(['student', 'bookCopy.book', 'libraryCard'])),
        ]);
    }

    public function renew(Request $request, int $id, LibraryService $libraryService)
    {
        $borrowing = BookBorrowing::with(['bookCopy.book', 'student'])->findOrFail($id);

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        if ($borrowing->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'Only active borrowings can be renewed.',
            ], 422);
        }

        try {
            $libraryService->renewBorrowing($borrowing, $validated['days'] ?? null);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Borrowing renewed.',
            'data' => $this->serializeBorrowing($borrowing->fresh(['student', 'bookCopy.book', 'libraryCard'])),
        ]);
    }

    protected function serializeBorrowing(BookBorrowing $b): array
    {
        $isOverdue = $b->status === 'borrowed' && $b->due_date !== null && $b->due_date->isPast();

        return [
            'id' => $b->id,
            'status' => $b->status,
            'is_overdue' => $isOverdue,
            'book_title' => $b->bookCopy?->book?->title,
            'copy_number' => $b->bookCopy?->copy_number,
            'student_id' => $b->student_id,
            'student_name' => $b->student?->full_name,
            'admission_number' => $b->student?->admission_number,
            'card_number' => $b->libraryCard?->card_number,
            'borrowed_date' => $b->borrowed_date?->format('Y-m-d'),
            'due_date' => $b->due_date?->format('Y-m-d'),
            'returned_date' => $b->returned_date?->format('Y-m-d'),
            'fine_amount' => $b->fine_amount !== null ? (float) $b->fine_amount : null,
            'can_return' => $b->status === 'borrowed',
            'can_renew' => $b->status === 'borrowed',
        ];
    }
}
