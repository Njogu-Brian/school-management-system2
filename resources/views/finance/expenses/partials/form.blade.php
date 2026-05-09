<div class="row g-3 mb-3">
  <div class="col-md-4">
    <label class="finance-form-label">Source Type</label>
    <select class="finance-form-select" name="source_type" required>
      @php($sourceType = old('source_type', $expense->source_type ?? 'vendor_bill'))
      @foreach(['vendor_bill' => 'Vendor Bill', 'reimbursement' => 'Reimbursement', 'direct_cash' => 'Direct Cash'] as $key => $label)
        <option value="{{ $key }}" @selected($sourceType === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label class="finance-form-label">Vendor</label>
    <select class="finance-form-select" name="vendor_id">
      <option value="">None</option>
      @foreach($vendors as $vendor)
        <option value="{{ $vendor->id }}" @selected((string)old('vendor_id', $expense->vendor_id ?? '') === (string)$vendor->id)>{{ $vendor->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-2"><label class="finance-form-label">Date</label><input type="date" name="expense_date" class="finance-form-control" value="{{ old('expense_date', optional($expense?->expense_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div>
  <div class="col-md-2"><label class="finance-form-label">Currency</label><input type="text" name="currency" class="finance-form-control" value="{{ old('currency', $expense->currency ?? 'KES') }}" required></div>
</div>

<h6>Lines</h6>
@php($lines = old('lines', $expense?->lines?->toArray() ?? [['description' => '', 'qty' => 1, 'unit_cost' => 0, 'tax_rate' => 0]]))
@foreach($lines as $i => $line)
<div class="row g-2 mb-2">
  <div class="col-md-3">
    <select class="finance-form-select" name="lines[{{ $i }}][category_id]" required>
      <option value="">Category</option>
      @foreach($categories as $category)
        <option value="{{ $category->id }}" @selected((string)($line['category_id'] ?? '') === (string)$category->id)>{{ $category->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4"><input class="finance-form-control" name="lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" placeholder="Description" required></div>
  <div class="col-md-1"><input class="finance-form-control" type="number" step="0.01" name="lines[{{ $i }}][qty]" value="{{ $line['qty'] ?? 1 }}" required></div>
  <div class="col-md-2"><input class="finance-form-control" type="number" step="0.01" name="lines[{{ $i }}][unit_cost]" value="{{ $line['unit_cost'] ?? 0 }}" required></div>
  <div class="col-md-2"><input class="finance-form-control" type="number" step="0.01" name="lines[{{ $i }}][tax_rate]" value="{{ $line['tax_rate'] ?? 0 }}" placeholder="Tax %"></div>
</div>
@endforeach

<div class="mb-3">
  <label class="finance-form-label">Notes</label>
  <textarea class="finance-form-control" name="notes">{{ old('notes', $expense->notes ?? '') }}</textarea>
</div>
