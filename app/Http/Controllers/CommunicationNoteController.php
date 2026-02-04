<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Services\CommunicationHelperService;
use Illuminate\Http\Request;

/**
 * Printed notes for parents (fee reminders, meeting reminders, etc.).
 * Draft message with placeholders, select recipients, then print notes with letterhead.
 */
class CommunicationNoteController extends Controller
{
    public function create()
    {
        abort_unless(can_access('communication', 'sms', 'add'), 403);

        $classes = Classroom::with('streams')->orderBy('name')->get();
        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->with(['classroom', 'parent'])
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        $systemPlaceholders = $this->getSystemPlaceholders();
        $customPlaceholders = class_exists(\App\Models\CustomPlaceholder::class)
            ? \App\Models\CustomPlaceholder::all()
            : collect();

        return view('communication.notes.create', compact(
            'classes',
            'students',
            'systemPlaceholders',
            'customPlaceholders'
        ));
    }

    /**
     * Open print view in new window: letterhead + one note per student (personalized).
     */
    public function printNotes(Request $request)
    {
        abort_unless(can_access('communication', 'sms', 'add'), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required|string|in:parents,class,student,specific_students',
            'classroom_id' => 'nullable|required_if:target,class|exists:classrooms,id',
            'student_id' => 'nullable|required_if:target,student|exists:students,id',
            'selected_student_ids' => 'nullable|string',
            'fee_balance_only' => 'nullable|boolean',
            'exclude_student_ids' => 'nullable|string',
        ]);
        $data['fee_balance_only'] = !empty($request->boolean('fee_balance_only'));

        $students = $this->collectNoteRecipients($data);
        if ($students->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'No recipients selected.'], 422);
            }
            $msg = 'No recipients selected.';
            if (!empty($data['fee_balance_only'])) {
                $msg = 'No students with an outstanding fee balance. If you selected "All students" with "Only recipients with fee balance" checked, every student may be clear or have overpayment—try unchecking the fee balance filter, or choose a class/specific students.';
            } else {
                $msg = 'No recipients selected. Please select All students, a class, student(s), or use the student selector.';
            }
            return redirect()->route('communication.notes.create')
                ->withInput()
                ->with('error', $msg);
        }

        $branding = $this->branding();
        $notes = [];
        foreach ($students as $student) {
            $extra = [];
            $invoices = \App\Models\Invoice::where('student_id', $student->id)->get();
            $totalOutstanding = $invoices->sum(fn ($inv) => max(0, (float) $inv->balance));
            $latestInvoice = \App\Models\Invoice::where('student_id', $student->id)
                ->orderBy('year', 'desc')
                ->orderBy('term', 'desc')
                ->first();
            $extra['outstanding_amount'] = number_format(round($totalOutstanding, 2), 2);
            $extra['total_amount'] = $latestInvoice ? number_format((float) $latestInvoice->total, 2) : '0.00';
            $extra['invoice_number'] = $latestInvoice ? ($latestInvoice->invoice_number ?? 'N/A') : 'N/A';
            $extra['due_date'] = $latestInvoice && $latestInvoice->due_date ? $latestInvoice->due_date->format('d M Y') : 'N/A';
            $notes[] = [
                'student' => $student,
                'body' => replace_placeholders($data['message'], $student, $extra),
            ];
        }

        return view('communication.notes.print', [
            'title' => $data['title'],
            'notes' => $notes,
            'branding' => $branding,
            'date' => now()->format('d M Y'),
        ]);
    }

    /**
     * Get unique students for notes based on target (one note per student, addressed to parent).
     */
    private function collectNoteRecipients(array $data): \Illuminate\Support\Collection
    {
        $target = $data['target'];
        $recipients = CommunicationHelperService::collectRecipients(
            array_merge($data, ['custom_emails' => null, 'custom_numbers' => null]),
            'email'
        );

        // contact => Student; we want unique students
        $students = collect($recipients)->filter(fn ($entity) => $entity instanceof Student)->values();
        $unique = $students->unique('id')->values();

        return $unique;
    }

    private function getSystemPlaceholders(): array
    {
        return [
            ['key' => 'school_name', 'value' => setting('school_name') ?? 'School Name'],
            ['key' => 'school_phone', 'value' => setting('school_phone') ?? 'School Phone'],
            ['key' => 'school_email', 'value' => setting('school_email') ?? 'School Email'],
            ['key' => 'date', 'value' => now()->format('d M Y')],
            ['key' => 'student_name', 'value' => "Student's full name"],
            ['key' => 'admission_number', 'value' => 'Student admission number'],
            ['key' => 'class_name', 'value' => 'Classroom name'],
            ['key' => 'parent_name', 'value' => "Parent's full name"],
            ['key' => 'father_name', 'value' => "Father's name"],
            ['key' => 'invoice_number', 'value' => 'Invoice number'],
            ['key' => 'total_amount', 'value' => 'Total amount'],
            ['key' => 'outstanding_amount', 'value' => 'Outstanding balance'],
            ['key' => 'due_date', 'value' => 'Due date'],
        ];
    }

    private function branding(): array
    {
        $kv = \Illuminate\Support\Facades\DB::table('settings')->pluck('value', 'key')->map(fn ($v) => trim((string) $v));

        $name = $kv['school_name'] ?? config('app.name', 'Your School');
        $email = $kv['school_email'] ?? '';
        $phone = $kv['school_phone'] ?? '';
        $website = $kv['school_website'] ?? '';
        $address = $kv['school_address'] ?? '';

        $logoFilename = $kv['school_logo'] ?? null;
        $logoPathSetting = $kv['school_logo_path'] ?? null;
        $candidates = [];
        if ($logoFilename) {
            $candidates[] = public_path('images/' . $logoFilename);
        }
        if ($logoPathSetting) {
            $candidates[] = public_path($logoPathSetting);
            $candidates[] = public_path('storage/' . $logoPathSetting);
            $candidates[] = storage_path('app/public/' . $logoPathSetting);
        }
        if (empty($candidates)) {
            $candidates[] = public_path('images/logo.png');
        }

        $logoBase64 = null;
        foreach ($candidates as $path) {
            if (! is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png');
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            break;
        }

        return compact('name', 'email', 'phone', 'website', 'address', 'logoBase64');
    }
}
