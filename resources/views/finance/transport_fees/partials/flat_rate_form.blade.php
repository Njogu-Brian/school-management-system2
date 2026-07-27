<div class="finance-card transport-sidebar-card shadow-sm rounded-4 border-0">
  <div class="finance-card-header">
    <i class="bi bi-sliders"></i>
    <span>Flat-rate Transport Fees</span>
  </div>
  <div class="finance-card-body p-4">
    <p class="transport-desc text-muted small mb-3">
      Set one transport fee amount for all existing <code>TransportFee</code> rows in the selected year/term.
      This updates <code>TransportFee.amount</code> (pricing_mode = imported). Run <strong>Post Pending Fees</strong> to update invoices.
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
          <label class="finance-form-label">Flat Amount (KES)</label>
          <input
            type="number"
            name="flat_amount"
            class="form-control"
            min="0"
            step="0.01"
            required
            value="{{ old('flat_amount') ?? 0 }}"
          >
          <small class="text-muted">Example: 3500.00</small>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-finance btn-finance-primary w-100" onclick="return confirm('Apply this flat amount to ALL existing transport fee rows for the selected year/term? Invoices will update after Post Pending Fees.');">
            <i class="bi bi-cash-coin me-2"></i>Apply Flat Rate
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

