<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PaymentThreshold;
use App\Models\StudentCategory;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentThresholdController extends Controller
{
    public function index(Request $request)
    {
        $terms = Term::orderByDesc('id')->with('academicYear')->get();
        $termId = $request->filled('term_id') ? (int) $request->get('term_id') : null;

        $query = PaymentThreshold::query()
            ->with(['term.academicYear', 'studentCategory', 'creator', 'updater'])
            ->orderByDesc('term_id')
            ->orderBy('student_category_id');

        if ($termId) {
            $query->where('term_id', $termId);
        }

        $thresholds = $query->paginate(25)->withQueryString();

        return view('finance.payment_thresholds.index', [
            'thresholds' => $thresholds,
            'terms' => $terms,
            'filterTermId' => $termId,
        ]);
    }

    public function create(Request $request)
    {
        $terms = Term::orderByDesc('id')->with('academicYear')->get();
        $categories = StudentCategory::orderBy('name')->get();

        return view('finance.payment_thresholds.create', [
            'terms' => $terms,
            'categories' => $categories,
            'selectedTermId' => old('term_id', $request->integer('term_id') ?: null),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'student_category_id' => [
                'required',
                'exists:student_categories,id',
                Rule::unique('payment_thresholds', 'student_category_id')->where(
                    fn ($q) => $q->where('term_id', $request->integer('term_id'))
                ),
            ],
            'minimum_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'final_deadline_day' => ['required', 'integer', 'min:1', 'max:31'],
            'final_deadline_month_offset' => ['required', 'integer', 'min:0', 'max:36'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'student_category_id.unique' => 'A threshold already exists for this term and student category.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        PaymentThreshold::create($validated);

        return redirect()
            ->route('finance.payment-thresholds.index', ['term_id' => $validated['term_id']])
            ->with('success', 'Payment threshold created. Run fee clearance recompute so student statuses update.');
    }

    public function edit(PaymentThreshold $payment_threshold)
    {
        $paymentThreshold = $payment_threshold;
        $terms = Term::orderByDesc('id')->with('academicYear')->get();
        $categories = StudentCategory::orderBy('name')->get();

        return view('finance.payment_thresholds.edit', [
            'paymentThreshold' => $paymentThreshold,
            'terms' => $terms,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, PaymentThreshold $payment_threshold)
    {
        $paymentThreshold = $payment_threshold;

        $validated = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'student_category_id' => [
                'required',
                'exists:student_categories,id',
                Rule::unique('payment_thresholds', 'student_category_id')
                    ->where(fn ($q) => $q->where('term_id', $request->integer('term_id')))
                    ->ignore($paymentThreshold->id),
            ],
            'minimum_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'final_deadline_day' => ['required', 'integer', 'min:1', 'max:31'],
            'final_deadline_month_offset' => ['required', 'integer', 'min:0', 'max:36'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'student_category_id.unique' => 'A threshold already exists for this term and student category.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['updated_by'] = auth()->id();

        $paymentThreshold->update($validated);

        return redirect()
            ->route('finance.payment-thresholds.index', ['term_id' => $validated['term_id']])
            ->with('success', 'Payment threshold updated. Recompute fee clearance if needed.');
    }

    public function destroy(PaymentThreshold $payment_threshold)
    {
        $termId = $payment_threshold->term_id;
        $payment_threshold->delete();

        return redirect()
            ->route('finance.payment-thresholds.index', array_filter(['term_id' => $termId]))
            ->with('success', 'Payment threshold removed. Students in that category may show as cleared (no threshold) until you add a new rule.');
    }
}
