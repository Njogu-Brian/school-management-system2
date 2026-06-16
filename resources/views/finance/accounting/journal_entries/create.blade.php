@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Manual Journal Entry', 'icon' => 'bi bi-pencil-square', 'subtitle' => 'Debits must equal credits'])

  <div class="finance-card"><div class="finance-card-body">
    <form method="POST" action="{{ route('finance.journal-entries.store') }}" id="journal-form">@csrf
      <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="entry_date" class="finance-form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="col-md-8"><label class="form-label">Description</label><input name="description" class="finance-form-control" required placeholder="e.g. Opening balance adjustment"></div>
      </div>

      <table class="finance-table" id="lines-table">
        <thead><tr><th>Account</th><th>Line description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th></th></tr></thead>
        <tbody>
          @for($i = 0; $i < 2; $i++)
          <tr class="journal-line">
            <td>
              <select name="lines[{{ $i }}][account_id]" class="finance-form-select" required>
                <option value="">Select account</option>
                @foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach
              </select>
            </td>
            <td><input name="lines[{{ $i }}][description]" class="finance-form-control"></td>
            <td><input name="lines[{{ $i }}][debit]" type="number" step="0.01" min="0" class="finance-form-control text-end debit-input"></td>
            <td><input name="lines[{{ $i }}][credit]" type="number" step="0.01" min="0" class="finance-form-control text-end credit-input"></td>
            <td></td>
          </tr>
          @endfor
        </tbody>
      </table>
      <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add-line">+ Add line</button>
      <div class="mt-3 d-flex justify-content-between align-items-center">
        <div id="totals" class="text-muted small">Debits: 0.00 | Credits: 0.00</div>
        <button class="btn btn-primary">Post Journal</button>
      </div>
    </form>
  </div></div>
</div></div>
@endsection

@push('scripts')
<script>
(function () {
  let lineIndex = 2;
  const accountsOptions = @json($accounts->map(fn($a) => ['id' => $a->id, 'label' => $a->code . ' — ' . $a->name])->values());

  function recalc() {
    let dr = 0, cr = 0;
    document.querySelectorAll('.debit-input').forEach(el => dr += parseFloat(el.value || 0));
    document.querySelectorAll('.credit-input').forEach(el => cr += parseFloat(el.value || 0));
    document.getElementById('totals').textContent = `Debits: ${dr.toFixed(2)} | Credits: ${cr.toFixed(2)}`;
  }

  document.getElementById('lines-table').addEventListener('input', recalc);

  document.getElementById('add-line').addEventListener('click', function () {
    const tbody = document.querySelector('#lines-table tbody');
    const tr = document.createElement('tr');
    tr.className = 'journal-line';
    let opts = '<option value="">Select account</option>';
    accountsOptions.forEach(a => { opts += `<option value="${a.id}">${a.label}</option>`; });
    tr.innerHTML = `
      <td><select name="lines[${lineIndex}][account_id]" class="finance-form-select" required>${opts}</select></td>
      <td><input name="lines[${lineIndex}][description]" class="finance-form-control"></td>
      <td><input name="lines[${lineIndex}][debit]" type="number" step="0.01" min="0" class="finance-form-control text-end debit-input"></td>
      <td><input name="lines[${lineIndex}][credit]" type="number" step="0.01" min="0" class="finance-form-control text-end credit-input"></td>
      <td><button type="button" class="btn btn-sm btn-link text-danger remove-line">×</button></td>`;
    tbody.appendChild(tr);
    lineIndex++;
    tr.querySelector('.remove-line').addEventListener('click', () => { tr.remove(); recalc(); });
  });
})();
</script>
@endpush
