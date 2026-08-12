<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesDocumentStorage;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ParentInfo;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiStudentDocumentsController extends Controller
{
    use ResolvesDocumentStorage;

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
            'download_path' => "/students/{$student->id}/documents/{$doc->id}/download",
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

    public function download(Request $request, int $studentId, int $documentId)
    {
        $student = Student::findOrFail($studentId);
        $document = Document::query()
            ->where('documentable_type', Student::class)
            ->where('documentable_id', $student->id)
            ->where('is_active', true)
            ->findOrFail($documentId);

        $disk = $this->resolveDiskForPath($document->file_path);
        if (! $disk) {
            abort(404, 'File not found.');
        }

        return Storage::disk($disk)->download($document->file_path, $document->file_name);
    }

    /**
     * Parent (or staff) upload: passport photo or birth certificate for a child.
     * POST /students/{id}/documents
     * multipart: file, category=student_profile_photo|student_birth_certificate
     */
    public function store(Request $request, int $id)
    {
        $user = $request->user();
        $student = Student::findOrFail($id);
        $this->authorizeParentOrStaff($user, $student);

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'category' => 'required|in:student_profile_photo,student_birth_certificate',
            'title' => 'nullable|string|max:190',
        ]);

        $file = $request->file('file');
        $category = $validated['category'];
        $isPhoto = $category === 'student_profile_photo';
        $disk = $isPhoto
            ? config('filesystems.public_disk', 'public')
            : config('filesystems.private_disk', 'private');
        $folder = $isPhoto ? 'student_photos' : 'birth_certificates';
        $path = $file->store($folder, $disk);

        $doc = Document::create([
            'title' => $validated['title'] ?? ($isPhoto ? 'Passport photo' : 'Birth certificate'),
            'description' => 'Uploaded via parent app',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'category' => $category,
            'document_type' => $isPhoto ? 'profile_photo' : 'birth_certificate',
            'documentable_type' => Student::class,
            'documentable_id' => $student->id,
            'version' => 1,
            'is_active' => true,
            'uploaded_by' => $user->id,
        ]);

        if ($isPhoto) {
            $student->photo_path = $path;
        } else {
            $student->birth_certificate_path = $path;
        }
        $student->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doc->id,
                'category' => $doc->category,
                'file_name' => $doc->file_name,
            ],
        ], 201);
    }

    /**
     * Upload one parent/guardian ID card for the linked parent_info.
     * POST /parent/documents/id-card
     */
    public function storeParentIdCard(Request $request)
    {
        $user = $request->user();
        if (! $user->parent_id) {
            return response()->json(['success' => false, 'message' => 'Not linked to a parent record.'], 403);
        }

        $parent = ParentInfo::findOrFail($user->parent_id);
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'slot' => 'nullable|in:father,mother,guardian',
            'title' => 'nullable|string|max:190',
        ]);

        $file = $request->file('file');
        $path = $file->store('parent_ids', config('filesystems.private_disk', 'private'));
        $slot = $validated['slot'] ?? 'guardian';

        $doc = Document::create([
            'title' => $validated['title'] ?? ucfirst($slot).' ID card',
            'description' => 'Uploaded via parent app',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'category' => 'parent_id_card',
            'document_type' => 'id_card',
            'documentable_type' => ParentInfo::class,
            'documentable_id' => $parent->id,
            'version' => 1,
            'is_active' => true,
            'uploaded_by' => $user->id,
        ]);

        $legacy = $slot.'_id_document';
        if (\Illuminate\Support\Facades\Schema::hasColumn('parent_info', $legacy)) {
            $parent->{$legacy} = $path;
            $parent->save();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doc->id,
                'category' => $doc->category,
                'slot' => $slot,
            ],
        ], 201);
    }

    protected function authorizeParentOrStaff($user, Student $student): void
    {
        if (! $user) {
            abort(401);
        }
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return;
        }
        if ($user->parent_id && $user->canAccessStudent((int) $student->id)) {
            return;
        }
        if ($user->shouldScopeAsParent() && $user->canAccessStudent((int) $student->id)) {
            return;
        }
        abort(403, 'You do not have access to upload documents for this student.');
    }
}
