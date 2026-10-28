@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3">
      <div class="finance-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h3 class="mb-1"><i class="bi bi-send-check"></i> Publish Invoices</h3>
          <p class="mb-0 finance-muted">
            Invoices created by Post Pending Fees stay hidden from parents until you publish them.
            Publishing sends WhatsApp, SMS (with invoice link), and an in-app notification that opens the invoice.
          </p>
        </div>
        <a href="{{ route('finance.posting.index') }}" class="btn btn-finance btn-finance-outline">
          <i class="bi bi-arrow-left"></i> Back to Posting
        </a>
      </div>
      <div class="finance-card-body">
        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
          <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if(session('info'))
          <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        <form method="GET" action="{{ route('finance.invoices.publish') }}" class="row g-3 mb-4">
          <div class="col-md-2">
            <label class="form-label">Academic Year</label>
            <select name="year" class="form-select">
              <option value="">All years</option>
              @foreach($academicYears as $year)
                <option value="{{ $year->year }}" @selected(($filters['year'] ?? null) == $year->year)>{{ $year->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Term</label>
            <select name="term" class="form-select">
              <option value="">All terms</option>
              @foreach([1,2,3] as $t)
                <option value="{{ $t }}" @selected(($filters['term'] ?? null) == $t)>Term {{ $t }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class_id" class="form-select">
              <option value="">Entire school</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(($filters['class_id'] ?? null) == $classroom->id)>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Stream</label>
            <select name="stream_id" class="form-select">
              <option value="">All streams</option>
              @foreach($streams as $stream)
                <option value="{{ $stream->id }}" @selected(($filters['stream_id'] ?? null) == $stream->id)>{{ $stream->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Student ID (optional)</label>
            <input type="number" name="student_id" class="form-control" value="{{ $filters['student_id'] ?? '' }}" placeholder="Specific student">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-finance btn-finance-outline">
              <i class="bi bi-funnel"></i> Preview unpublished
            </button>
          </div>
        </form>

        <div class="alert alert-primary d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <strong>{{ number_format($pendingCount) }}</strong> unpublished invoice(s) match this scope.
            @if($preview->count() < $pendingCount)
              Showing first {{ $preview->count() }}.
            @endif
          </div>
        </div>

        <form method="POST" action="{{ route('finance.invoices.publish.submit') }}"
              onsubmit="return confirm('Publish {{ $pendingCount }} invoice(s) and notify parents via the selected channels?');">
          @csrf
          <input type="hidden" name="year" value="{{ $filters['year'] ?? '' }}">
          <input type="hidden" name="term" value="{{ $filters['term'] ?? '' }}">
          <input type="hidden" name="class_id" value="{{ $filters['class_id'] ?? '' }}">
          <input type="hidden" name="stream_id" value="{{ $filters['stream_id'] ?? '' }}">
          <input type="hidden" name="student_id" value="{{ $filters['student_id'] ?? '' }}">

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Notify parents via</label>
              <div class="d-flex flex-wrap gap-3">
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" checked>
                  <span class="form-check-label">WhatsApp (invoice link)</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="channels[]" value="sms" checked>
                  <span class="form-check-label">SMS (invoice link)</span>
                </label>
              </div>
              <div class="form-text">In-app notification is always sent and opens the invoice in the Users app.</div>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <button type="submit" class="btn btn-finance btn-finance-primary" {{ $pendingCount < 1 ? 'disabled' : '' }}>
                <i class="bi bi-send-check"></i> Publish &amp; notify parents
              </button>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Student</th>
                <th>Class</th>
                <th>Year / Term</th>
                <th class="text-end">Total</th>
                <th class="text-end">Balance</th>
              </tr>
            </thead>
            <tbody>
              @forelse($preview as $invoice)
                <tr>
                  <td>
                    <a href="{{ route('finance.invoices.show', $invoice) }}">{{ $invoice->invoice_number ?: '#'.$invoice->id }}</a>
                    <span class="badge bg-secondary ms-1">Draft</span>
                  </td>
                  <td>{{ $invoice->student?->full_name ?? trim(($invoice->student->first_name ?? '').' '.($invoice->student->last_name ?? '')) }}</td>
                  <td>{{ $invoice->displayClassroomName() }}</td>
                  <td>{{ $invoice->year }} / Term {{ $invoice->term }}</td>
                  <td class="text-end">{{ number_format((float) $invoice->total, 2) }}</td>
                  <td class="text-end">{{ number_format((float) $invoice->balance, 2) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center finance-muted py-4">No unpublished invoices in this scope.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
