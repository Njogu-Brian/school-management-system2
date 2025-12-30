@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Report Cards</div>
        <h1 class="mb-1">Report Cards</h1>
        <p class="text-muted mb-0">View, publish, and manage report cards.</p>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
      <div class="text-muted small">Select report cards to send via SMS / Email / WhatsApp.</div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-settings-primary"
          onclick="openSendDocument('report_card', collectCheckedIds('.rc-checkbox'))">
          <i class="bi bi-send"></i> Send Selected
        </button>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:32px;"><input type="checkbox" id="rcCheckAll"></th>
                <th>Student</th>
                <th>Class</th>
                <th>Term</th>
                <th>Year</th>
                <th>Status</th>
                <th>Published</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($report_cards as $rc)
                <tr>
                    <td><input type="checkbox" class="form-check-input rc-checkbox" value="{{ $rc->id }}"></td>
                  <td>{{ $rc->student->full_name }}</td>
                  <td>{{ $rc->classroom->name ?? '' }} {{ $rc->stream->name ?? '' }}</td>
                  <td>{{ $rc->term->name ?? '' }}</td>
                  <td>{{ $rc->academicYear->year ?? '' }}</td>
                  <td>
                    @if($rc->locked_at)
                      <span class="pill-badge pill-danger">Locked</span>
                    @elseif($rc->published_at)
                      <span class="pill-badge pill-success">Published</span>
                    @else
                      <span class="pill-badge pill-warning">Draft</span>
                    @endif
                  </td>
                  <td>{{ $rc->published_at ? $rc->published_at->format('d M Y') : '-' }}</td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.report_cards.show',$rc) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      @if(!$rc->locked_at)
                        <a href="{{ route('academics.report_cards.edit',$rc) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('academics.report_cards.destroy',$rc) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this report card?')">
                          @csrf @method('DELETE')
                          <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                      @endif
                      <button type="button" class="btn btn-sm btn-ghost-strong text-success" title="Send"
                        onclick="openSendDocument('report_card', [{{ $rc->id }}])">
                        <i class="bi bi-send"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No report cards found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $report_cards->links() }}</div>
    </div>
  </div>
</div>

@include('communication.partials.document-send-modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const checkAll = document.getElementById('rcCheckAll');
  const boxes = document.querySelectorAll('.rc-checkbox');
  function refresh() {
    if (checkAll) {
      const allChecked = boxes.length && Array.from(boxes).every(b => b.checked);
      checkAll.checked = allChecked;
    }
  }
  checkAll?.addEventListener('change', () => {
    boxes.forEach(b => b.checked = checkAll.checked);
  });
  boxes.forEach(b => b.addEventListener('change', refresh));
  refresh();
});
</script>
@endpush
@endsection
