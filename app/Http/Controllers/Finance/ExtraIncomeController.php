<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\ActivityFeeAllocation;
use App\Models\ExtraIncomeItem;
use App\Models\InvoiceItem;
use App\Models\OptionalFee;
use App\Models\Student;
use App\Models\Votehead;
use App\Services\ExtraIncomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExtraIncomeController extends Controller
{
    public function __construct(protected ExtraIncomeService $extraIncome)
    {
    }

    public function index()
    {
        $items = ExtraIncomeItem::query()
            ->with(['classroom', 'classrooms', 'votehead', 'academicYear'])
            ->withCount('allocations')
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderByDesc('term')
            ->orderBy('name')
            ->get();

        return view('finance.extra-income.index', compact('items'));
    }

    public function create()
    {
        return view('finance.extra-income.create', $this->formData());
    }

    public function store(Request $request)
    {
        [$data, $classroomIds] = $this->validated($request);
        $chargeClass = $request->boolean('charge_class');

        $item = ExtraIncomeItem::create($data);
        $item->syncClassrooms($classroomIds);

        if (!$item->isSwimming() && !$item->votehead_id) {
            $this->extraIncome->ensureVotehead($item);
        }

        if ($chargeClass && !$item->isSwimming()) {
            try {
                $count = $this->extraIncome->chargeClass($item->fresh(['classroom', 'classrooms', 'votehead']));
            } catch (\Throwable $e) {
                return redirect()
                    ->route('finance.extra-income.show', $item)
                    ->with('success', 'Extra income saved.')
                    ->withErrors(['error' => $e->getMessage()]);
            }

            return redirect()
                ->route('finance.extra-income.show', $item)
                ->with('success', "Extra income saved and charged to {$count} student(s) in the selected class(es).");
        }

        return redirect()
            ->route('finance.extra-income.show', $item)
            ->with('success', 'Extra income saved.');
    }

    public function show(ExtraIncomeItem $extraIncome)
    {
        $extraIncome->load(['classroom', 'classrooms', 'votehead', 'academicYear']);

        $students = collect();
        $optionalFees = collect();
        $invoiceItems = collect();
        $classroomIds = $extraIncome->classroomIds();

        if ($classroomIds !== []) {
            $students = Student::query()
                ->with(['stream', 'classroom'])
                ->whereIn('classroom_id', $classroomIds)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->orderBy('classroom_id')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }

        if ($extraIncome->votehead_id && $students->isNotEmpty()) {
            $ids = $students->pluck('id');
            $optionalFees = OptionalFee::query()
                ->where('votehead_id', $extraIncome->votehead_id)
                ->where('year', $extraIncome->year)
                ->where('term', $extraIncome->term)
                ->whereIn('student_id', $ids)
                ->get()
                ->keyBy('student_id');

            $invoiceItems = InvoiceItem::query()
                ->where('votehead_id', $extraIncome->votehead_id)
                ->where('status', 'active')
                ->whereHas('invoice', function ($q) use ($extraIncome, $ids) {
                    $q->where('year', $extraIncome->year)
                        ->where('term', $extraIncome->term)
                        ->whereIn('student_id', $ids)
                        ->whereNull('reversed_at');
                })
                ->with('invoice')
                ->get()
                ->keyBy(fn (InvoiceItem $line) => $line->invoice->student_id);
        }

        $received = ActivityFeeAllocation::query()
            ->where('extra_income_item_id', $extraIncome->id)
            ->where('status', ActivityFeeAllocation::STATUS_ALLOCATED)
            ->selectRaw('student_id, SUM(amount) as paid')
            ->groupBy('student_id')
            ->pluck('paid', 'student_id');

        return view('finance.extra-income.show', compact(
            'extraIncome',
            'students',
            'optionalFees',
            'invoiceItems',
            'received'
        ));
    }

    public function edit(ExtraIncomeItem $extraIncome)
    {
        $extraIncome->load('classrooms');

        return view('finance.extra-income.edit', array_merge(
            $this->formData(),
            ['item' => $extraIncome]
        ));
    }

    public function update(Request $request, ExtraIncomeItem $extraIncome)
    {
        [$data, $classroomIds] = $this->validated($request, $extraIncome);
        $chargeClass = $request->boolean('charge_class');

        $extraIncome->update($data);
        $extraIncome->syncClassrooms($classroomIds);

        if (!$extraIncome->isSwimming() && !$extraIncome->votehead_id) {
            $this->extraIncome->ensureVotehead($extraIncome);
        }

        if ($chargeClass && !$extraIncome->isSwimming()) {
            try {
                $count = $this->extraIncome->chargeClass($extraIncome->fresh(['classroom', 'classrooms', 'votehead']));
            } catch (\Throwable $e) {
                return redirect()
                    ->route('finance.extra-income.show', $extraIncome)
                    ->with('success', 'Extra income updated.')
                    ->withErrors(['error' => $e->getMessage()]);
            }

            return redirect()
                ->route('finance.extra-income.show', $extraIncome)
                ->with('success', "Extra income updated and charged to {$count} student(s) in the selected class(es).");
        }

        return redirect()
            ->route('finance.extra-income.show', $extraIncome)
            ->with('success', 'Extra income updated.');
    }

    public function charge(ExtraIncomeItem $extraIncome)
    {
        try {
            $count = $this->extraIncome->chargeClass($extraIncome->loadMissing(['classroom', 'classrooms', 'votehead']));
        } catch (\Throwable $e) {
            return redirect()
                ->route('finance.extra-income.show', $extraIncome)
                ->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('finance.extra-income.show', $extraIncome)
            ->with('success', "Charged {$extraIncome->name} to {$count} student(s).");
    }

    public function destroy(ExtraIncomeItem $extraIncome)
    {
        $hasMoney = $extraIncome->allocations()
            ->where('status', ActivityFeeAllocation::STATUS_ALLOCATED)
            ->exists();

        if ($hasMoney) {
            $extraIncome->update(['is_active' => false]);

            return redirect()
                ->route('finance.extra-income.index')
                ->with('success', 'This activity already has payments, so it was closed instead of deleted.');
        }

        $extraIncome->delete();

        return redirect()
            ->route('finance.extra-income.index')
            ->with('success', 'Extra income deleted.');
    }

    protected function formData(): array
    {
        $years = AcademicYear::query()->orderByDesc('year')->get();
        $currentYear = $years->firstWhere('is_active', true) ?? $years->first();

        return [
            'kinds' => ExtraIncomeItem::KINDS,
            'classrooms' => Classroom::query()
                ->where(function ($q) {
                    $q->where('is_alumni', false)->orWhereNull('is_alumni');
                })
                ->orderBy('name')
                ->get(),
            'years' => $years,
            'currentYearId' => $currentYear?->id,
            'currentTerm' => (int) setting('current_term', 1),
            'voteheads' => Votehead::query()
                ->where('is_active', true)
                ->where('is_mandatory', false)
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<int>}
     */
    protected function validated(Request $request, ?ExtraIncomeItem $existing = null): array
    {
        $kind = (string) $request->input('kind');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'kind' => ['required', Rule::in(array_keys(ExtraIncomeItem::KINDS))],
            'classroom_ids' => [
                Rule::requiredIf($kind !== ExtraIncomeItem::KIND_SWIMMING),
                'nullable',
                'array',
            ],
            'classroom_ids.*' => 'integer|exists:classrooms,id',
            'votehead_id' => [
                'nullable',
                'exists:voteheads,id',
                function ($attribute, $value, $fail) use ($kind) {
                    if (!$value || $kind === ExtraIncomeItem::KIND_SWIMMING) {
                        return;
                    }
                    $votehead = Votehead::find($value);
                    if ($votehead && $votehead->is_mandatory) {
                        $fail('Choose an optional activity votehead, or leave it blank to create one for this activity.');
                    }
                },
            ],
            'academic_year_id' => 'required|exists:academic_years,id',
            'term' => 'required|integer|in:1,2,3',
            'amount' => 'required|numeric|min:0.01',
            'event_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        $year = AcademicYear::findOrFail($data['academic_year_id']);
        $classroomIds = collect($data['classroom_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return [
            [
                'name' => $data['name'],
                'kind' => $data['kind'],
                'classroom_id' => $classroomIds[0] ?? null,
                'votehead_id' => $data['kind'] === ExtraIncomeItem::KIND_SWIMMING
                    ? null
                    : ($data['votehead_id'] ?? $existing?->votehead_id),
                'academic_year_id' => $year->id,
                'year' => (int) $year->year,
                'term' => (int) $data['term'],
                'amount' => round((float) $data['amount'], 2),
                'event_date' => $data['event_date'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => $existing?->created_by ?? Auth::id(),
            ],
            $classroomIds,
        ];
    }
}
