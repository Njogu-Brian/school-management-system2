<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::withCount('copies');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $books = $query->latest()->paginate(20)->withQueryString();

        return view('library.books.index', compact('books'));
    }

    public function create()
    {
        return view('library.books.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'isbn' => 'nullable|string|unique:books,isbn',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'category' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:50',
            'total_copies' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $book = Book::create($validated);

        // Create book copies
        for ($i = 1; $i <= $validated['total_copies']; $i++) {
            BookCopy::create([
                'book_id' => $book->id,
                'copy_number' => "Copy {$i}",
                'barcode' => $this->generateBarcode($book->id, $i),
                'status' => 'available',
            ]);
        }

        $book->updateAvailableCount();

        return redirect()
            ->route('library.books.show', $book)
            ->with('success', 'Book added successfully.');
    }

    public function show(Book $book)
    {
        $book->load(['copies', 'reservations.student']);
        return view('library.books.show', compact('book'));
    }

    public function edit(Book $book)
    {
        return view('library.books.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'isbn' => 'nullable|string|unique:books,isbn,' . $book->id,
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'category' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $book->update($validated);

        return redirect()
            ->route('library.books.show', $book)
            ->with('success', 'Book updated successfully.');
    }

    protected function generateBarcode(int $bookId, int $copyNumber): string
    {
        return 'BK-' . str_pad($bookId, 6, '0', STR_PAD_LEFT) . '-' . str_pad($copyNumber, 3, '0', STR_PAD_LEFT);
    }
}

