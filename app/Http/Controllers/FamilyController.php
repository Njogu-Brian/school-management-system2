<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyController extends Controller
{
    public function index()
    {
        $families = Family::with(['students.classroom', 'students.stream'])
            ->withCount('students')
            ->orderBy('guardian_name')
            ->paginate(15);

        $balances = $this->familyBalances();

        return view('families.index', compact('families', 'balances'));
    }

    public function create()
    {
        $students = Student::with(['classroom', 'stream'])
            ->whereNull('family_id')
            ->orderBy('first_name')
            ->get();

        return view('families.create', compact('students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'guardian_name' => 'required|string|max:255',
            'phone'         => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $family = DB::transaction(function () use ($data) {
            $family = Family::create([
                'guardian_name' => $data['guardian_name'],
                'phone'         => $data['phone'] ?? null,
                'email'         => $data['email'] ?? null,
            ]);

            $studentIds = collect($data['student_ids'] ?? [])->filter()->values();
            if ($studentIds->isNotEmpty()) {
                Student::whereIn('id', $studentIds)->update(['family_id' => $family->id]);
            }

            return $family;
        });

        return redirect()
            ->route('families.edit', $family)
            ->with('success', 'Family created and linked successfully.');
    }

    public function edit(Family $family)
    {
        $family->load('students.classroom', 'students.stream');

        $availableStudents = Student::with(['classroom', 'stream'])
            ->where(function ($q) use ($family) {
                $q->whereNull('family_id')->orWhere('family_id', $family->id);
            })
            ->orderBy('first_name')
            ->get();

        return view('families.edit', [
            'family'            => $family,
            'availableStudents' => $availableStudents,
        ]);
    }

    public function update(Request $request, Family $family)
    {
        $data = $request->validate([
            'guardian_name' => 'required|string|max:255',
            'phone'         => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        DB::transaction(function () use ($family, $data) {
            $family->update([
                'guardian_name' => $data['guardian_name'],
                'phone'         => $data['phone'] ?? null,
                'email'         => $data['email'] ?? null,
            ]);

            $studentIds = collect($data['student_ids'] ?? [])->filter()->values();

            Student::where('family_id', $family->id)
                ->whereNotIn('id', $studentIds)
                ->update(['family_id' => null]);

            if ($studentIds->isNotEmpty()) {
                Student::whereIn('id', $studentIds)->update(['family_id' => $family->id]);
            }
        });

        return redirect()
            ->route('families.edit', $family)
            ->with('success', 'Family details updated.');
    }

    public function destroy(Family $family)
    {
        if ($family->students()->exists()) {
            return redirect()
                ->route('families.index')
                ->with('error', 'Cannot delete a family while students are linked.');
        }

        $family->delete();

        return redirect()
            ->route('families.index')
            ->with('success', 'Family deleted.');
    }

    protected function familyBalances(): array
    {
        $payments = Payment::selectRaw('invoice_id, SUM(amount) as paid')
            ->groupBy('invoice_id');

        return Invoice::selectRaw('students.family_id as family_id, SUM(GREATEST(invoices.total - COALESCE(p.paid, 0), 0)) as balance')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoinSub($payments, 'p', 'p.invoice_id', '=', 'invoices.id')
            ->whereNotNull('students.family_id')
            ->whereIn('invoices.status', ['unpaid', 'partial'])
            ->groupBy('students.family_id')
            ->pluck('balance', 'family_id')
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }
}
