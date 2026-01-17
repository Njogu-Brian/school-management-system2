@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Balance Brought Forward Import Preview',
        'icon' => 'bi bi-clipboard-check',
        'subtitle' => 'Review and compare imported values with system balances'
    ])

    @if($hasIssues)
      <div class="alert alert-warning alert-dismissible fade show finance-animate" role="alert">
        <strong><i class="bi bi-exclamation-triangle"></i> Issues detected!</strong> Please review the differences below before committing the import.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @else
      <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
        <strong><i class="bi bi-check-circle"></i> All values match!</strong> No differences found between system and import values.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-list-check"></i>
          <span>Comparison Results</span>
        </div>
        <div>
          @php
            $okCount = collect($preview)->where('status', 'ok')->count();
            $issueCount = collect($preview)->where('status', '!=', 'ok')->count();
          @endphp
          <span class="badge bg-success">{{ $okCount }} OK</span>
          @if($issueCount > 0)
            <span class="badge bg-warning text-dark">{{ $issueCount }} Issues</span>
          @endif
        </div>
      </div>
      <div class="finance-card-body p-4">
        <form method="POST" action="{{ route('finance.balance-brought-forward.import.commit') }}" id="importForm">
          @csrf
          
          <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle"></i> 
            <strong>Instructions:</strong> 
            <ul class="mb-0 mt-2">
              <li>Use <strong>Match Student</strong> to search and manually match students (includes archived and alumni)</li>
              <li>Check <strong>Skip</strong> to exclude a row from the import</li>
              <li>Choose <strong>Use Import</strong> or <strong>Use System</strong> balance when amounts differ</li>
            </ul>
          </div>

          <div class="finance-table-wrapper mb-3">
            <div class="table-responsive">
              <table class="finance-table align-middle">
                <thead>
                  <tr>
                    <th style="width: 50px;">Skip</th>
                    <th>Match Student</th>
                    <th>Current Student / Admission #</th>
                    <th class="text-end">System Balance</th>
                    <th class="text-end">Import Balance</th>
                    <th>Use Balance</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($preview as $index => $row)
                    @php
                      $rowId = 'row_' . $index;
                      $isIssue = $row['status'] !== 'ok';
                      $rowClass = match($row['status']) {
                        'student_not_found' => 'table-danger',
                        'exists_in_system_only' => 'table-warning',
                        'exists_in_import_only' => 'table-info',
                        'amount_differs' => 'table-warning',
                        default => ''
                      };
                      $hasSystemBalance = $row['system_balance'] !== null && $row['system_balance'] > 0;
                      $hasImportBalance = $row['import_balance'] !== null && $row['import_balance'] > 0;
                      $needsChoice = $hasSystemBalance && $hasImportBalance && abs($row['system_balance'] - $row['import_balance']) > 0.01;
                    @endphp
                    <tr class="{{ $rowClass }} import-row" data-row-id="{{ $rowId }}">
                      <td class="text-center">
                        <input type="checkbox" 
                               class="form-check-input skip-row" 
                               name="skip[]" 
                               value="{{ $index }}"
                               id="skip_{{ $index }}">
                      </td>
                      <td>
                        <div class="student-search-wrapper" style="min-width: 250px;">
                          @include('partials.student_live_search', [
                            'hiddenInputId' => 'matched_student_id_' . $index,
                            'displayInputId' => 'student_search_' . $index,
                            'resultsId' => 'student_results_' . $index,
                            'placeholder' => 'Search student...',
                            'includeAlumniArchived' => true,
                            'inputClass' => 'form-control form-control-sm'
                          ])
                        </div>
                        <input type="hidden" 
                               name="rows[{{ $index }}][matched_student_id]" 
                               id="row_matched_student_id_{{ $index }}"
                               value="{{ $row['student_id'] ?? '' }}"
                               data-row-index="{{ $index }}">
                        <input type="hidden" name="rows[{{ $index }}][original_student_id]" value="{{ $row['student_id'] ?? '' }}">
                        <input type="hidden" name="rows[{{ $index }}][original_admission_number]" value="{{ $row['admission_number'] ?? '' }}">
                        <input type="hidden" name="rows[{{ $index }}][import_balance]" value="{{ $row['import_balance'] ?? 0 }}">
                        <input type="hidden" name="rows[{{ $index }}][system_balance]" value="{{ $row['system_balance'] ?? 0 }}">
                      </td>
                      <td>
                        @if($row['student_id'])
                          <div class="fw-semibold">{{ $row['student_name'] }}</div>
                          <small class="text-muted">{{ $row['admission_number'] }}</small>
                        @else
                          <div class="text-muted">
                            <div>{{ $row['student_name'] ?? $row['admission_number'] }}</div>
                            <small>Not matched</small>
                          </div>
                        @endif
                      </td>
                      <td class="text-end">
                        @if($hasSystemBalance)
                          <strong>KES {{ number_format($row['system_balance'], 2) }}</strong>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="text-end">
                        @if($hasImportBalance)
                          <strong>KES {{ number_format($row['import_balance'], 2) }}</strong>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td>
                        @if($needsChoice)
                          <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" 
                                   class="btn-check balance-choice" 
                                   name="rows[{{ $index }}][use_balance]" 
                                   id="use_import_{{ $index }}" 
                                   value="import"
                                   checked>
                            <label class="btn btn-outline-primary" for="use_import_{{ $index }}">Import</label>
                            
                            <input type="radio" 
                                   class="btn-check balance-choice" 
                                   name="rows[{{ $index }}][use_balance]" 
                                   id="use_system_{{ $index }}" 
                                   value="system">
                            <label class="btn btn-outline-secondary" for="use_system_{{ $index }}">System</label>
                          </div>
                        @elseif($hasImportBalance && !$hasSystemBalance)
                          <span class="badge bg-info">Will use Import</span>
                        @elseif($hasSystemBalance && !$hasImportBalance)
                          <span class="badge bg-warning text-dark">System Only</span>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td>
                        @if($row['status'] === 'ok')
                          <span class="badge bg-success">Match</span>
                        @elseif($row['status'] === 'student_not_found')
                          <span class="badge bg-danger">Student Not Found</span>
                        @elseif($row['status'] === 'exists_in_system_only')
                          <span class="badge bg-warning text-dark">System Only</span>
                        @elseif($row['status'] === 'exists_in_import_only')
                          <span class="badge bg-info">Import Only</span>
                        @elseif($row['status'] === 'amount_differs')
                          <span class="badge bg-warning text-dark">Amount Differs</span>
                        @endif
                        @if($row['message'])
                          <br><small class="text-muted">{{ $row['message'] }}</small>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
                @if(count($preview) > 0)
                <tfoot>
                  <tr>
                    <th colspan="3" class="text-end">Totals</th>
                    <th class="text-end">
                      KES {{ number_format(collect($preview)->where('system_balance', '!=', null)->sum('system_balance'), 2) }}
                    </th>
                    <th class="text-end">
                      KES {{ number_format(collect($preview)->where('import_balance', '!=', null)->sum('import_balance'), 2) }}
                    </th>
                    <th colspan="2"></th>
                  </tr>
                </tfoot>
                @endif
              </table>
            </div>
          </div>

          <div class="d-flex gap-3">
            <a href="{{ route('finance.balance-brought-forward.index') }}" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left"></i> Back
            </a>
            <div class="alert alert-info mb-0 d-flex align-items-center gap-2 flex-grow-1">
              <i class="bi bi-info-circle"></i>
              <span id="import-summary">Ready to import. Skipped rows and rows without matched students will be excluded.</span>
            </div>
            <button type="submit" class="btn btn-finance btn-finance-primary" id="commitBtn">
              <i class="bi bi-check2-circle"></i> Commit Import
            </button>
          </div>
        </form>
      </div>
    </div>

    @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('importForm');
        const commitBtn = document.getElementById('commitBtn');
        const summaryEl = document.getElementById('import-summary');
        const skipCheckboxes = document.querySelectorAll('.skip-row');
        const balanceChoices = document.querySelectorAll('.balance-choice');

        // Update summary when selections change
        function updateSummary() {
          const totalRows = {{ count($preview) }};
          const skippedCount = Array.from(skipCheckboxes).filter(cb => cb.checked).length;
          const activeRows = totalRows - skippedCount;
          
          summaryEl.textContent = `Ready to import ${activeRows} row(s). ${skippedCount} row(s) will be skipped.`;
        }

        // Handle skip checkboxes
        skipCheckboxes.forEach(cb => {
          cb.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
              row.classList.add('table-secondary');
              row.style.opacity = '0.6';
            } else {
              row.classList.remove('table-secondary');
              row.style.opacity = '1';
            }
            updateSummary();
          });
        });

        // Handle student selection from search
        window.addEventListener('student-selected', function(e) {
          const student = e.detail;
          // Find the hidden input that was just updated by the student search component
          setTimeout(() => {
            const searchWrappers = document.querySelectorAll('.student-live-search');
            searchWrappers.forEach(wrapper => {
              const hiddenInWrapper = wrapper.querySelector('input[type="hidden"][id^="matched_student_id_"]');
              if (hiddenInWrapper && hiddenInWrapper.value == student.id) {
                const match = hiddenInWrapper.id.match(/matched_student_id_(\d+)/);
                if (match) {
                  const index = match[1];
                  const row = wrapper.closest('tr');
                  if (row) {
                    // Update the matched student ID input that will be submitted in the form
                    const rowMatchedInput = document.getElementById(`row_matched_student_id_${index}`);
                    if (rowMatchedInput) {
                      rowMatchedInput.value = student.id;
                    }
                    // Update the display
                    const studentCell = row.querySelector('td:nth-child(3)');
                    if (studentCell) {
                      studentCell.innerHTML = `
                        <div class="fw-semibold">${student.full_name}</div>
                        <small class="text-muted">${student.admission_number}</small>
                      `;
                    }
                    // Remove error styling if any
                    row.classList.remove('table-danger');
                  }
                }
              }
            });
            updateSummary();
          }, 50);
        });

        // Update summary on initial load
        updateSummary();
      });
    </script>
    @endpush

    @if($hasIssues)
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mt-4">
        <div class="finance-card-header">
          <i class="bi bi-info-circle"></i>
          <span>Legend</span>
        </div>
        <div class="finance-card-body p-4">
          <div class="row">
            <div class="col-md-6">
              <ul class="list-unstyled">
                <li><span class="badge bg-success">Match</span> - System and import values match</li>
                <li><span class="badge bg-warning text-dark">Amount Differs</span> - Values exist in both but amounts differ</li>
                <li><span class="badge bg-warning text-dark">System Only</span> - Value exists in system but not in import</li>
              </ul>
            </div>
            <div class="col-md-6">
              <ul class="list-unstyled">
                <li><span class="badge bg-info">Import Only</span> - Value exists in import but not in system (will be added)</li>
                <li><span class="badge bg-danger">Student Not Found</span> - Admission number in import doesn't match any student</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

