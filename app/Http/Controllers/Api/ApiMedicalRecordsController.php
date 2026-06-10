<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentMedicalRecord;
use Illuminate\Http\Request;

class ApiMedicalRecordsController extends Controller
{
    public function index(Request $request, int $studentId)
    {
        $student = Student::findOrFail($studentId);
        $perPage = min((int) $request->input('per_page', 20), 100);

        $paginated = $student->medicalRecords()
            ->with('createdBy')
            ->orderByDesc('record_date')
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (StudentMedicalRecord $r) => $this->serialize($r))->values();

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

    public function store(Request $request, int $studentId)
    {
        $student = Student::findOrFail($studentId);

        $data = $request->validate([
            'record_type' => 'required|in:vaccination,checkup,medication,incident,certificate,other',
            'record_date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'doctor_name' => 'nullable|string|max:255',
            'clinic_hospital' => 'nullable|string|max:255',
            'medication_name' => 'nullable|string|max:255',
            'medication_dosage' => 'nullable|string|max:255',
            'vaccination_name' => 'nullable|string|max:255',
            'vaccination_date' => 'nullable|date',
            'next_due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $record = StudentMedicalRecord::create([
            ...$data,
            'student_id' => $student->id,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medical record added.',
            'data' => $this->serialize($record->load('createdBy')),
        ], 201);
    }

    public function show(int $studentId, int $id)
    {
        $record = StudentMedicalRecord::query()
            ->where('student_id', $studentId)
            ->with('createdBy')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($record),
        ]);
    }

    protected function serialize(StudentMedicalRecord $r): array
    {
        return [
            'id' => $r->id,
            'student_id' => $r->student_id,
            'record_type' => $r->record_type,
            'record_date' => $r->record_date?->format('Y-m-d'),
            'title' => $r->title,
            'description' => $r->description,
            'doctor_name' => $r->doctor_name,
            'clinic_hospital' => $r->clinic_hospital,
            'medication_name' => $r->medication_name,
            'medication_dosage' => $r->medication_dosage,
            'vaccination_name' => $r->vaccination_name,
            'vaccination_date' => $r->vaccination_date?->format('Y-m-d'),
            'next_due_date' => $r->next_due_date?->format('Y-m-d'),
            'certificate_type' => $r->certificate_type,
            'notes' => $r->notes,
            'created_by' => $r->createdBy?->name,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }
}
