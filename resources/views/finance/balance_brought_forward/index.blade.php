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
    @if(session('errors') && is_array(session('errors')))
      <div class="alert alert-warning alert-dismissible fade show finance-animate" role="alert">
        <strong>Some errors occurred:</strong>
        <ul class="mb-0">
          @foreach(session('errors') as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                          <td class="text-end fw-bold">KES {{ number_format($balance, 2) }}</td>
                          <td>
                            <span class="badge bg-info">{{ $source }}</span>
                          </td>
                          <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $student->id }}">
                              <i class="bi bi-pencil"></i> Edit
                            </button>
                          </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal{{ $student->id }}" tabindex="-1">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <form method="POST" action="{{ route('finance.balance-brought-forward.update', $student) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                  <h5 class="modal-title">Update Balance Brought Forward</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                  <div class="mb-3">
                                    <label class="form-label">Student</label>
                                    <input type="text" class="form-control" value="{{ $student->full_name }} ({{ $student->admission_number }})" readonly>
                                  </div>
                                  <div class="mb-3">
                                    <label class="form-label">Current Balance Brought Forward</label>
                                    <input type="text" class="form-control" value="KES {{ number_format($balance, 2) }}" readonly>
                                  </div>
                                  <div class="mb-3">
                                    <label class="form-label">New Balance Brought Forward <span class="text-danger">*</span></label>
                                    <input type="number" name="balance" class="form-control" step="0.01" min="0" value="{{ $balance }}" required>
                                    <small class="text-muted">Enter the new balance brought forward amount</small>
                                  </div>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                  <button type="submit" class="btn btn-primary">Update</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
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
              <input type="hidden" name="student_id" id="add_balance_student_id">
              <div class="mb-3">
                <label class="finance-form-label">Student</label>
                @include('partials.student_live_search', [
                    'hiddenInputId' => 'add_balance_student_id',
                    'displayInputId' => 'addBalanceStudentSearch',
                    'resultsId' => 'addBalanceStudentResults',
                    'placeholder' => 'Type name or admission #',
                    'initialLabel' => ''
                ])
              </div>
              <div class="mb-3">
                <label class="finance-form-label">Balance Brought Forward <span class="text-danger">*</span></label>
                <input type="number" name="balance" class="form-control" step="0.01" min="0" required>
                <small class="text-muted">Enter the balance brought forward amount</small>
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

