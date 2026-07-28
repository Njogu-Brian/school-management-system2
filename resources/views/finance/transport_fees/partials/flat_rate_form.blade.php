<div class="finance-card transport-sidebar-card shadow-sm rounded-4 border-0">
  <div class="finance-card-header">
    <i class="bi bi-sliders"></i>
    <span>Flat-rate Transport Adjustment</span>
  </div>
  <div class="finance-card-body p-4">
    <p class="transport-desc text-muted small mb-3">
      Add or subtract the same amount from every existing transport fee row in the selected year/term.
      Example: a student on KES 5,400 with a KES 500 increase becomes KES 5,900.
      Run <strong>Post Pending Fees</strong> to update invoices.
    </p>

    <form method="POST" action="{{ route('finance.transport-fees.flat-rate') }}">
      @csrf
      <div class="row g-3">
        <div class="col-12">
          <label class="finance-form-label">Year/Term</label>
          <select name="year_term" class="finance-form-select" required>
            @foreach(($termsByYear ?? collect())->sortKeysDesc() as $yr => $terms)
              @foreach($terms as $t)
                @php $termNum = (int) preg_replace('/[^0-9]/', '', $t->name) ?: 1; @endphp
                <option value="{{ ($t->academicYear->year ?? $yr) . '|' . $termNum }}"
                  @selected(($year ?? '') == ($t->academicYear->year ?? $yr) && ($term ?? '') == $termNum)>
                  {{ $t->academicYear->year ?? $yr }} - {{ $t->name }}
                </option>
              @endforeach
            @endforeach
          </select>
        </div>

        <div class="col-12">
          <label class="finance-form-label">Adjustment</label>
          <select name="adjustment_type" class="finance-form-select" required>
            <option value="increase" @selected(old('adjustment_type', 'increase') === 'increase')>Increase (add to existing amounts)</option>
            <option value="decrease" @selected(old('adjustment_type') === 'decrease')>Decrease (subtract from existing amounts)</option>
          </select>
        </div>

        <div class="col-12">
          <label class="finance-form-label">Amount (KES)</label>
          <input
            type="number"
            name="flat_amount"
            class="form-control"
            min="0"
            step="0.01"
            required
            value="{{ old('flat_amount') ?? 0 }}"
          >
          <small class="text-muted">Example: 500.00 adds or subtracts KES 500 from each student's current transport fee.</small>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-finance btn-finance-primary w-100" onclick="return confirm('Apply this adjustment to ALL existing transport fee rows for the selected year/term? Invoices will update after Post Pending Fees.');">
            <i class="bi bi-cash-coin me-2"></i>Apply Adjustment
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

