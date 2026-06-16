@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', [
    'title' => 'Journal Entries',
    'icon' => 'bi bi-journal-text',
    'actions' => '<a href="'.route('finance.journal-entries.create').'" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus"></i> Manual Entry</a>'
  ])

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Entry No</th><th>Date</th><th>Description</th><th>Source</th><th class="text-end">Debits</th><th class="text-end">Credits</th><th></th></tr></thead>
      <tbody>
        @forelse($entries as $entry)
          <tr>
            <td>{{ $entry->entry_no }}</td>
            <td>{{ $entry->entry_date->format('d M Y') }}</td>
            <td>{{ Str::limit($entry->description, 50) }}</td>
            <td><span class="badge bg-light text-dark">{{ $entry->source_type ?? 'manual' }}</span></td>
            <td class="text-end">{{ number_format($entry->totalDebits(), 2) }}</td>
            <td class="text-end">{{ number_format($entry->totalCredits(), 2) }}</td>
            <td><a href="{{ route('finance.journal-entries.show', $entry) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-muted">No journal entries yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $entries->links() }}
</div></div>
@endsection
