<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Student;
use Illuminate\Http\Request;

class ApiStudentDocumentsController extends Controller
{
    public function index(Request $request, int $id)
    {
        $student = Student::findOrFail($id);
        $perPage = (int) $request->input('per_page', 30);

        $query = Document::query()
            ->where('documentable_type', Student::class)
            ->where('documentable_id', $student->id)
            ->where('is_active', true)
            ->orderByDesc('created_at');

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (Document $doc) => [
            'id' => $doc->id,
            'title' => $doc->title ?? '',
            'description' => $doc->description,
            'category' => $doc->category,
            'document_type' => $doc->document_type,
            'file_name' => $doc->file_name,
            'file_type' => $doc->file_type,
            'file_size' => $doc->file_size,
            'file_url' => $doc->file_url,
            'version' => $doc->version,
            'created_at' => $doc->created_at?->toIso8601String(),
            'updated_at' => $doc->updated_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'student_id' => $student->id,
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
