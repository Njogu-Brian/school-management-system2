@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Transport Fees',
      'icon' => 'bi bi-bus-front',
      'subtitle' => 'Manage transport charges per term and keep invoices in sync'
  ])

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
          <i class="bi bi-clipboard-data"></i>
          <span>Current transport charges</span>
        </div>
        <div class="finance-card-body p-4">
          <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="finance-form-label">Classroom</label>
              <select name="classroom_id" class="finance-form-select" onchange="this.form.submit()">
                <option value="">All classes</option>
                @foreach($classrooms as $class)
                  <option value="{{ $class->id }}" @selected($classroomId == $class->id)>{{ $class->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="finance-form-label">Year</label>
              <input type="number" name="year" class="finance-form-control" value="{{ $year }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
              <label class="finance-form-label">Term</label>
              <select name="term" class="finance-form-select" onchange="this.form.submit()">
                @foreach([1,2,3] as $t)
                  <option value="{{ $t }}" @selected($term == $t)>Term {{ $t }}</option>
                @endforeach
              </select>
            </div>
          </form>

          <form method="POST" action="{{ route('finance.transport-fees.bulk-update') }}">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="term" value="{{ $term }}">

            <div class="finance-table-wrapper">
              <div class="table-responsive">
                <table class="finance-table align-middle">
                  <thead>
                    <tr>
                      <th>Student</th>
                      <th>Class</th>
                      <th>Drop-off point</th>
                      <th class="text-end">Amount (KES)</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($students as $student)
                      @php
                        $fee = $feeMap[$student->id] ?? null;
                        $amount = old("fees.{$student->id}.amount", $fee?->amount);
                      @endphp
                      <tr>
                        <td>
                          <div class="fw-semibold">{{ $student->full_name }}</div>
                          <div class="text-muted small">Adm: {{ $student->admission_number }}</div>
                        </td>
                        <td>{{ $student->classroom?->name ?? '—' }}</td>
                        <td style="min-width: 240px;">
                          <select name="fees[{{ $student->id }}][drop_off_point_id]" class="finance-form-select">
                            <option value="">—</option>
                            @foreach($dropOffPoints as $point)
                              <option value="{{ $point->id }}" @selected(old("fees.{$student->id}.drop_off_point_id", $fee?->drop_off_point_id) == $point->id)>
                                {{ $point->name }}
                              </option>
                            @endforeach
                          </select>
                          <input type="text" name="fees[{{ $student->id }}][drop_off_point_name]" class="finance-form-control mt-2" placeholder="Other / custom" value="{{ old("fees.{$student->id}.drop_off_point_name", $fee?->drop_off_point_name) }}">
                        </td>
                        <td class="text-end">
                          <input type="number" step="0.01" name="fees[{{ $student->id }}][amount]" class="finance-form-control text-end" value="{{ $amount }}">
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="4" class="text-center text-muted">No students with transport assignments found for this filter.</td>
                      </tr>
                    @endforelse
                  </tbody>
                  @if($students->count())
                  <tfoot>
                    <tr>
                      <th colspan="3" class="text-end">Current total</th>
                      <th class="text-end">{{ number_format($totalAmount, 2) }}</th>
                    </tr>
                  </tfoot>
                  @endif
                </table>
              </div>
            </div>

            <div class="d-flex gap-3 mt-3">
              <button type="submit" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-save"></i> Update transport fees
              </button>
              <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">
                Manage drop-off points
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="finance-card shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
          <i class="bi bi-upload"></i>
          <span>Import transport fees</span>
        </div>
        <div class="finance-card-body p-4">
          <p class="text-muted">Upload an Excel file with columns: Admission Number, Student Name, Transport Fee, Drop-off Point.</p>
          <form method="POST" action="{{ route('finance.transport-fees.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label class="finance-form-label">File (.xlsx/.csv)</label>
              <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="finance-form-label">Year</label>
                <input type="number" name="year" class="finance-form-control" value="{{ $year }}" required>
              </div>
              <div class="col-md-6">
                <label class="finance-form-label">Term</label>
                <select name="term" class="finance-form-select" required>
                  @foreach([1,2,3] as $t)
                    <option value="{{ $t }}" @selected($term == $t)>Term {{ $t }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <button class="btn btn-finance btn-finance-primary w-100 mt-3">
              <i class="bi bi-eye"></i> Preview &amp; apply
            </button>
          </form>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <a class="btn btn-link p-0" href="{{ route('finance.transport-fees.template') }}">
              <i class="bi bi-download"></i> Download template
            </a>
          </div>
          <div class="alert alert-info mt-3 mb-0 small">
            <div class="fw-semibold mb-1">Heads-up</div>
            Transport charges are written directly to invoices (one invoice per student per term) without creating debit/credit notes.
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

