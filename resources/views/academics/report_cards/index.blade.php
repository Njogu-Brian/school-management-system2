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
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('academics.report_cards.index') }}" id="reportCardFiltersForm">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Search</label>
              <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Student name or admission no.">
            </div>
            @include('partials.academic_year_term_selects', [
              'years' => $years,
              'terms' => $terms,
              'selectedYearId' => $selectedYearId ?? null,
              'selectedTermId' => $selectedTermId ?? null,
              'allowEmptyYear' => true,
              'allowEmptyTerm' => true,
              'yearCol' => 'col-md-2',
              'termCol' => 'col-md-2',
            ])
            <div class="col-md-2">
              <label class="form-label">Class</label>
              <select name="classroom_id" class="form-select" id="filterClassroomId">
                <option value="">All classes</option>
                @foreach($classrooms as $c)
                  <option value="{{ $c->id }}" @selected((string) request('classroom_id') === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Stream</label>
              <select name="stream_id" class="form-select" id="filterStreamId">
                <option value="">All streams</option>
                @foreach($streams as $s)
                  <option value="{{ $s->id }}"
                    data-classroom-id="{{ $s->classroom_id }}"
                    @selected((string) request('stream_id') === (string) $s->id)>{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-1">
              <label class="form-label">Per page</label>
              <select name="per_page" class="form-select">
                @foreach([10, 50, 100, 200] as $size)
                  <option value="{{ $size }}" @selected((int) ($perPage ?? 20) === $size)>{{ $size }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label d-block">&nbsp;</label>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('academics.report_cards.index') }}" class="btn btn-ghost-strong">Reset</a>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    @can('report_cards.publish')
      @php
        $publishYearId = $selectedYearId ?? request('academic_year_id');
        $publishTermId = $selectedTermId ?? request('term_id');
        $publishHasCriteria = !empty($publishYearId) && !empty($publishTermId);
      @endphp
    @endcan

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
      <div class="text-muted small">Select report cards to send via SMS / Email / WhatsApp.</div>
      <div class="d-flex gap-2 flex-wrap">
        @can('report_cards.publish')
          @if($publishHasCriteria)
            <form method="POST" action="{{ route('academics.report_cards.bulk_publish_from_filters_no_notify') }}" onsubmit="return confirm('Publish report cards only (no SMS/Email/WhatsApp)?');" class="d-inline">
              @csrf
              <input type="hidden" name="academic_year_id" value="{{ $publishYearId }}">
              <input type="hidden" name="term_id" value="{{ $publishTermId }}">
              @if(!empty(request('classroom_id')))
                <input type="hidden" name="classroom_id" value="{{ request('classroom_id') }}">
              @endif
              @if(!empty(request('stream_id')))
                <input type="hidden" name="stream_id" value="{{ request('stream_id') }}">
              @endif
              <button type="submit" class="btn btn-settings-primary">
                <i class="bi bi-upload"></i> Publish Matching Reports
              </button>
            </form>
          @else
            <span class="text-muted small">Choose Academic Year and Term, then filter to publish.</span>
          @endif
        @endcan
        <button type="button" class="btn btn-ghost-strong"
          onclick="openSendDocument('report_card', collectCheckedIds('.rc-checkbox'))">
          <i class="bi bi-send"></i> Send Selected
        </button>
        @can('report_cards.export_pdf')
          <button type="button" class="btn btn-ghost-strong" id="bulkPrintSelectedBtn" title="Print selected report cards">
            <i class="bi bi-printer"></i> Print Selected
          </button>
          <button type="button" class="btn btn-ghost-strong" id="bulkPrintFilteredBtn" title="Print all report cards matching current filters">
            <i class="bi bi-printer-fill"></i> Print Matching
          </button>
        @endcan
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted small mb-0">Showing {{ $report_cards->total() }} report card(s)</div>
      </div>
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
                        @if(!$rc->published_at)
                        <form action="{{ route('academics.report_cards.publish', $rc) }}" method="POST" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-ghost-strong text-primary" title="Publish only">
                            <i class="bi bi-upload"></i>
                          </button>
                        </form>
                        @endif
                        <form action="{{ route('academics.report_cards.destroy',$rc) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this report card?')">
                          @csrf @method('DELETE')
                          <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                      @endif
                      <button type="button" class="btn btn-sm btn-ghost-strong text-success" title="Send"
                        onclick="openSendDocument('report_card', [{{ $rc->id }}])">
                        <i class="bi bi-send"></i>
                      </button>
                      @can('report_cards.export_pdf')
                        <a href="{{ route('academics.report_cards.pdf', $rc) }}" class="btn btn-sm btn-ghost-strong text-secondary" title="Download PDF" target="_blank">
                          <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                      @endcan
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No report cards found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form method="GET" action="{{ route('academics.report_cards.index') }}" class="d-flex align-items-center gap-2">
          <input type="hidden" name="search" value="{{ request('search') }}">
          <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') }}">
          <input type="hidden" name="term_id" value="{{ request('term_id') }}">
          <input type="hidden" name="classroom_id" value="{{ request('classroom_id') }}">
          <input type="hidden" name="stream_id" value="{{ request('stream_id') }}">
          <label class="small text-muted mb-0">Show</label>
          <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach([10, 50, 100, 200] as $size)
              <option value="{{ $size }}" @selected((int) ($perPage ?? 20) === $size)>{{ $size }}</option>
            @endforeach
          </select>
          <span class="small text-muted">per page</span>
        </form>
        {{ $report_cards->links() }}
      </div>
    </div>
  </div>
</div>

@include('communication.partials.document-send-modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const classSelect = document.getElementById('filterClassroomId');
  const streamSelect = document.getElementById('filterStreamId');
  if (classSelect && streamSelect) {
    const allStreamOptions = Array.from(streamSelect.options).slice(1);
    const syncStreams = function() {
      const classId = this.value;
      const current = streamSelect.value;
      streamSelect.innerHTML = '<option value="">All streams</option>';
      allStreamOptions.forEach(option => {
        if (!classId || option.dataset.classroomId === classId) {
          streamSelect.appendChild(option.cloneNode(true));
        }
      });
      if (current && Array.from(streamSelect.options).some(o => o.value === current)) {
        streamSelect.value = current;
      }
    };
    classSelect.addEventListener('change', syncStreams);
    syncStreams.call(classSelect);
  }

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

  function collectFilterParams() {
    const form = document.getElementById('reportCardFiltersForm');
    const params = new URLSearchParams();
    if (!form) return params;
    new FormData(form).forEach((value, key) => {
      if (value !== '') params.append(key, value);
    });
    return params;
  }

  function openBulkPrint(extraParams = {}) {
    const params = collectFilterParams();
    Object.entries(extraParams).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.set(key, value);
      }
    });
    const url = '{{ route('academics.report_cards.bulk_print') }}?' + params.toString();
    window.open(url, '_blank');
  }

  document.getElementById('bulkPrintSelectedBtn')?.addEventListener('click', function() {
    const ids = Array.from(document.querySelectorAll('.rc-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) {
      alert('Select at least one report card to print.');
      return;
    }
    openBulkPrint({ ids: ids.join(',') });
  });

  document.getElementById('bulkPrintFilteredBtn')?.addEventListener('click', function() {
    openBulkPrint();
  });
});
</script>
@endpush
@endsection
