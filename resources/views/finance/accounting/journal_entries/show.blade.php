@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Journal Entry ' . $entry->entry_no, 'icon' => 'bi bi-journal-text'])

  <div class="finance-card mb-3"><div class="finance-card-body">
    <p class="mb-1"><strong>Date:</strong> {{ $entry->entry_date->format('d M Y') }}</p>
    <p class="mb-1"><strong>Description:</strong> {{ $entry->description }}</p>
    <p class="mb-0"><strong>Source:</strong> {{ $entry->source_type ?? 'manual' }} @if($entry->source_id)#{{ $entry->source_id }}@endif</p>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
      <tbody>
        @foreach($entry->lines as $line)
          <tr>
            <td>{{ $line->account->code }} — {{ $line->account->name }}</td>
            <td>{{ $line->description }}</td>
            <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
            <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-end">Totals</th>
          <th class="text-end">{{ number_format($entry->totalDebits(), 2) }}</th>
          <th class="text-end">{{ number_format($entry->totalCredits(), 2) }}</th>
        </tr>
      </tfoot>
    </table>
  </div>
</div></div>
@endsection
