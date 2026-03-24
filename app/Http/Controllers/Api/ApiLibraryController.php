<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
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

        $paginated = $query->orderBy('title')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn($b) => [
            'id' => $b->id,
            'title' => $b->title ?? '',
            'author' => $b->author ?? null,
            'isbn' => $b->isbn ?? null,
            'status' => $b->status ?? 'available',
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
}
