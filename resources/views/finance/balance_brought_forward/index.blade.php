@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Balance Brought Forward',
        'icon' => 'bi bi-arrow-left-circle',
        'subtitle' => 'View and manage balance brought forward from legacy imports or previous terms'
    ])

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if(session('warning'))
      <div class="alert alert-warning alert-dismissible fade show finance-animate" role="alert">
        <i class="bi bi-info-circle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
        <strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>
        <ul class="mb-0 mt-2">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
          <div class="finance-card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-list-ul"></i>
              <span>Students with Balance Brought Forward</span>
            </div>
            <span class="badge bg-primary">{{ $students->count() }} students</span>
          </div>
          <div class="finance-card-body p-4">
            @if($students->count() > 0)
              <div class="finance-table-wrapper">
                <div class="table-responsive">
                  <table class="finance-table align-middle">
                    <thead>
                      <tr>
                        <th>Student</th>
                        <th>Admission #</th>
                        <th>Class</th>
                        <th class="text-end">Balance Brought Forward</th>
                        <th>Source</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($students as $item)
                        @php
                          $student = $item['student'];
                          $balance = $item['balance_brought_forward'];
                          $source = $item['source'];
                        @endphp
                        <tr>
                          <td>
                            <div class="fw-semibold">{{ $student->full_name }}</div>
                          </td>
                          <td>{{ $student->admission_number }}</td>
                          <td>{{ $student->classroom?->name ?? '—' }}</td>
                          <td class="text-end">
                            <div class="d-flex flex-column flex-md-row align-items-end gap-2">
                              <span class="fw-bold">KES {{ number_format($balance, 2) }}</span>
                              <form method="POST" action="{{ route('finance.balance-brought-forward.update', $student) }}" class="d-flex align-items-center gap-1 flex-nowrap" style="max-width: 200px;">
                                @csrf
                                @method('PUT')
                                <input type="number" name="balance" class="form-control form-control-sm" step="0.01" min="0" value="{{ $balance }}" required style="width: 100px;">
                                <button type="submit" class="btn btn-sm btn-primary" title="Save changes">
                                  <i class="bi bi-check-lg"></i> Save
                                </button>
                              </form>
                            </div>
                          </td>
                          <td>
                            <span class="badge bg-info">{{ $source }}</span>
                          </td>
                          <td>
                            <form method="POST" action="{{ route('finance.balance-brought-forward.destroy', $student) }}" class="d-inline" onsubmit="return confirm('Remove balance brought forward for this student? The amount will no longer appear on their statement or invoice.');">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove balance brought forward">
                                <i class="bi bi-trash"></i> Delete
                              </button>
                            </form>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                    @if($students->count() > 0)
                    <tfoot>
                      <tr>
                        <th colspan="3" class="text-end">Total Balance Brought Forward</th>
                        <th class="text-end">KES {{ number_format($students->sum(fn($item) => $item['balance_brought_forward']), 2) }}</th>
                        <th colspan="2"></th>
                      </tr>
                    </tfoot>
                    @endif
                  </table>
                </div>
              </div>
            @else
              <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">No students with balance brought forward found.</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="finance-card shadow-sm rounded-4 border-0 mb-4">
          <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i>
            <span>Add Balance Brought Forward</span>
          </div>
          <div class="finance-card-body p-4">
            <p class="text-muted small">Search for a student to add or update their balance brought forward.</p>
            <form method="POST" action="{{ route('finance.balance-brought-forward.add') }}" id="addBalanceForm">
              @csrf
              <div class="mb-3">
                <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                @include('partials.student_live_search', [
                    'hiddenInputId' => 'add_balance_student_id',
                    'displayInputId' => 'addBalanceStudentSearch',
                    'resultsId' => 'addBalanceStudentResults',
                    'placeholder' => 'Type name or admission #',
                    'initialLabel' => old('student_id') ? (\App\Models\Student::find(old('student_id'))?->full_name ?? '') : ''
                ])
                @error('student_id')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
              <div class="mb-3">
                <label class="finance-form-label">Balance Brought Forward <span class="text-danger">*</span></label>
                <input type="number" name="balance" class="form-control @error('balance') is-invalid @enderror" step="0.01" min="0" value="{{ old('balance') }}" required>
                <small class="text-muted">Enter the balance brought forward amount</small>
                @error('balance')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                <i class="bi bi-plus-circle"></i> Add/Update Balance
              </button>
            </form>
          </div>
        </div>

        <div class="finance-card shadow-sm rounded-4 border-0">
          <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-upload"></i>
            <span>Import Balance Brought Forward</span>
          </div>
          <div class="finance-card-body p-4">
            <p class="text-muted">Upload an Excel file with columns: <strong>Admission Number</strong> and <strong>Balance Brought Forward</strong>.</p>
            <p class="text-muted small">The system will compare imported values with existing balances and highlight any differences.</p>
            <form method="POST" action="{{ route('finance.balance-brought-forward.import.preview') }}" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label class="finance-form-label">File (.xlsx/.xls/.csv)</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
              </div>
              <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                <i class="bi bi-eye"></i> Preview & Compare
              </button>
            </form>
            <div class="d-flex justify-content-center align-items-center mt-3">
              <a class="btn btn-link p-0" href="{{ route('finance.balance-brought-forward.import.template') }}">
                <i class="bi bi-download"></i> Download template
              </a>
            </div>
            <div class="alert alert-info mt-3 mb-0 small">
              <div class="fw-semibold mb-1">How it works</div>
              <ul class="mb-0">
                <li>Import balances will be compared with system values</li>
                <li>Differences will be highlighted for review</li>
                <li>You can update balances individually or commit the import</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

