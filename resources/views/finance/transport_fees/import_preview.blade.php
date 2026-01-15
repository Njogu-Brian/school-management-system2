@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Transport Fee Import Preview',
      'icon' => 'bi bi-upload',
      'subtitle' => 'Validate rows before applying to invoices'
  ])

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @php
    $newCount = collect($preview)->where('change_type', 'new')->where('status', 'ok')->count();
    $existingCount = collect($preview)->where('change_type', 'existing')->count();
    $changedCount = collect($preview)->whereIn('change_type', ['changed_amount', 'changed_dropoff', 'changed_both'])->count();
    $needsMatchingCount = collect($preview)->where('status', 'needs_matching')->count();
    $needsConfirmationCount = collect($preview)->where('needs_confirmation', true)->count();
    $missingStudentCount = collect($preview)->where('status', 'missing_student')->count();
    
    // Group preview by category
    $groupedPreview = [
      'new' => collect($preview)->where('change_type', 'new')->where('status', 'ok')->values(),
      'changed' => collect($preview)->whereIn('change_type', ['changed_amount', 'changed_dropoff', 'changed_both'])->values(),
      'needs_matching' => collect($preview)->where('status', 'needs_matching')->values(),
      'missing_student' => collect($preview)->where('status', 'missing_student')->values(),
      'needs_confirmation' => collect($preview)->where('needs_confirmation', true)->where('status', '!=', 'needs_matching')->values(),
      'other' => collect($preview)->whereNotIn('change_type', ['new', 'existing', 'changed_amount', 'changed_dropoff', 'changed_both'])
        ->where('status', '!=', 'needs_matching')
        ->where('status', '!=', 'missing_student')
        ->where('needs_confirmation', false)
        ->values(),
    ];
  @endphp

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
    <div class="finance-card-header d-flex align-items-center gap-2">
      <i class="bi bi-info-circle"></i>
      <span>Import Summary</span>
    </div>
    <div class="finance-card-body p-4">
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
              <strong>Year:</strong> {{ $year }}
            </div>
            <div>
              <strong>Term:</strong> {{ $term }}
            </div>
            <div>
              <strong>Total Amount:</strong> 
              <span class="fw-bold text-success">{{ number_format($total, 2) }}</span>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="d-flex gap-2 flex-wrap">
            @if($newCount > 0) 
              <span class="badge bg-success fs-6">{{ $newCount }} New</span> 
            @endif
            @if($changedCount > 0) 
              <span class="badge bg-warning text-dark fs-6">{{ $changedCount }} Changed</span> 
            @endif
            @if($existingCount > 0) 
              <span class="badge bg-secondary fs-6">{{ $existingCount }} Already Billed</span> 
            @endif
            @if($needsMatchingCount > 0) 
              <span class="badge bg-warning text-dark fs-6">{{ $needsMatchingCount }} Need Matching</span> 
            @endif
            @if($missingStudentCount > 0) 
              <span class="badge bg-danger fs-6">{{ $missingStudentCount }} Missing Student</span> 
            @endif
            @if($needsConfirmationCount > 0) 
              <span class="badge bg-info fs-6">{{ $needsConfirmationCount }} Need Confirmation</span> 
            @endif
          </div>
        </div>
      </div>
      @if($total == 0)
        <div class="alert alert-warning mb-0">
          <i class="bi bi-exclamation-triangle"></i> 
          <strong>Warning:</strong> Total amount is 0.00. Please verify that your Excel file has amounts in the "Transport Fee" column.
        </div>
      @endif
    </div>
  </div>

  @if($needsMatchingCount > 0)
    <div class="alert alert-warning mb-4">
      <div class="fw-semibold mb-2">
        <i class="bi bi-people"></i> Students Need Matching
      </div>
      <p class="small mb-0">Some students have multiple matches. Please select the correct student for each row below.</p>
    </div>
  @endif

  @if($missingStudentCount > 0)
    <div class="alert alert-danger mb-4">
      <div class="fw-semibold mb-2">
        <i class="bi bi-exclamation-triangle"></i> Students Not Found
      </div>
      <p class="small mb-0">Some students were not found in the system. Please search and select the correct student for each row below.</p>
    </div>
  @endif

  @if($needsConfirmationCount > 0)
    <div class="alert alert-info mb-4">
      <div class="fw-semibold mb-2">
        <i class="bi bi-question-circle"></i> Changes Detected
      </div>
      <p class="small mb-0">Some students already have transport fees with different amounts or drop-off points. Please confirm whether to use the new values or keep the existing ones.</p>
    </div>
  @endif

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
    <div class="finance-card-header d-flex align-items-center gap-2">
      <i class="bi bi-list-check"></i>
      <span>Preview Details</span>
    </div>
    <div class="finance-card-body p-4">
      <form method="POST" action="{{ route('finance.transport-fees.import.commit') }}" id="importForm">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="term" value="{{ $term }}">

        <div class="finance-table-wrapper mb-4">
          @foreach($groupedPreview as $category => $rows)
            @if($rows->count() > 0)
              @php
                $categoryTitles = [
                  'new' => 'New Entries',
                  'changed' => 'Changed Entries',
                  'needs_matching' => 'Students Need Matching',
                  'missing_student' => 'Students Not Found',
                  'needs_confirmation' => 'Needs Confirmation',
                  'other' => 'Other',
                ];
                $categoryColors = [
                  'new' => 'success',
                  'changed' => 'warning',
                  'needs_matching' => 'warning',
                  'missing_student' => 'danger',
                  'needs_confirmation' => 'info',
                  'other' => 'secondary',
                ];
              @endphp
              <div class="mb-4">
                <h5 class="mb-3 d-flex align-items-center gap-2">
                  <span class="badge bg-{{ $categoryColors[$category] }} fs-6">{{ $rows->count() }}</span>
                  <span>{{ $categoryTitles[$category] }}</span>
                </h5>
                <div class="table-responsive">
                  <table class="finance-table align-middle">
                    <thead>
                      <tr>
                        <th style="min-width: 200px;">Student</th>
                        <th style="min-width: 120px;">Admission #</th>
                        <th class="text-end" style="min-width: 120px;">Amount</th>
                        <th style="min-width: 180px;">Drop-off Point</th>
                        <th style="min-width: 150px;">Change Type</th>
                        <th style="min-width: 150px;">Status</th>
                        <th style="min-width: 200px;">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($rows as $rowIndex => $row)
                        @php 
                          $index = array_search($row, $preview);
                          $isOk = in_array($row['status'] ?? '', ['ok', 'already_billed']);
                          $changeType = $row['change_type'] ?? 'new';
                          $needsMatching = ($row['status'] ?? '') === 'needs_matching';
                          $missingStudent = ($row['status'] ?? '') === 'missing_student';
                          $needsConfirmation = $row['needs_confirmation'] ?? false;
                        @endphp
                        <tr class="{{ $isOk && !$needsMatching && !$needsConfirmation && !$missingStudent ? '' : ($needsConfirmation ? 'table-info' : ($missingStudent ? 'table-danger' : 'table-warning')) }}">
                          <td>
                            <div class="fw-semibold">{{ $row['student_name'] ?? '—' }}</div>
                          </td>
                          <td>
                            <code>{{ $row['admission_number'] ?? '—' }}</code>
                          </td>
                          <td class="text-end">
                            @if($row['existing_amount'] ?? null)
                              <div class="d-flex flex-column align-items-end">
                                <span class="text-decoration-line-through text-muted small">
                                  {{ number_format($row['existing_amount'], 2) }}
                                </span>
                                <span class="fw-bold text-success">
                                  {{ number_format($row['amount'], 2) }}
                                </span>
                              </div>
                            @else
                              <span class="fw-semibold">{{ $row['amount'] !== null ? number_format($row['amount'], 2) : '—' }}</span>
                            @endif
                          </td>
                          <td>
                            @if($row['existing_drop_off_point_name'] ?? null)
                              <div class="d-flex flex-column">
                                <span class="text-decoration-line-through text-muted small">
                                  {{ $row['existing_drop_off_point_name'] ?? 'None' }}
                                </span>
                                <span class="fw-semibold">
                                  {{ $row['drop_off_point_name'] ?? ($row['drop_off_point_id'] ? $dropOffPoints->firstWhere('id', $row['drop_off_point_id'])->name ?? '—' : '—') }}
                                </span>
                              </div>
                            @else
                              <span>{{ $row['drop_off_point_name'] ?? ($row['drop_off_point_id'] ? $dropOffPoints->firstWhere('id', $row['drop_off_point_id'])->name ?? '—' : '—') }}</span>
                            @endif
                          </td>
                          <td>
                            @if($changeType === 'new')
                              <span class="badge bg-success">New</span>
                            @elseif($changeType === 'existing')
                              <span class="badge bg-secondary">Existing</span>
                            @elseif($changeType === 'changed_amount')
                              <span class="badge bg-warning text-dark">Amount Changed</span>
                            @elseif($changeType === 'changed_dropoff')
                              <span class="badge bg-warning text-dark">Drop-off Changed</span>
                            @elseif($changeType === 'changed_both')
                              <span class="badge bg-danger">Both Changed</span>
                            @endif
                          </td>
                          <td>
                            @if($isOk && !$needsMatching && !$needsConfirmation && !$missingStudent)
                              <span class="badge bg-success">Ready</span>
                            @elseif($needsMatching)
                              <span class="badge bg-warning text-dark">Select Student</span>
                            @elseif($missingStudent)
                              <span class="badge bg-danger">Student Not Found</span>
                            @elseif($needsConfirmation)
                              <span class="badge bg-info">Needs Confirmation</span>
                            @elseif($row['status'] === 'own_means')
                              <span class="badge bg-info">Own Means (No Fee)</span>
                            @else
                              <span class="badge bg-warning text-dark">{{ $row['message'] ?? 'Needs attention' }}</span>
                            @endif
                          </td>
                          <td>
                            @if($needsMatching && !empty($row['matched_students']))
                              <select name="student_matches[{{ $index }}]" class="form-select form-select-sm student-match-select" required>
                                <option value="">Select Student...</option>
                                @foreach($row['matched_students'] as $match)
                                  <option value="{{ $match['id'] }}">{{ $match['name'] }} ({{ $match['admission_number'] }})</option>
                                @endforeach
                              </select>
                            @elseif($missingStudent || ($changeType === 'new' && !($row['student_id'] ?? null)))
                              <div class="d-flex flex-column gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary search-student-btn" 
                                        data-index="{{ $index }}"
                                        data-student-name="{{ $row['student_name'] ?? '' }}"
                                        data-admission="{{ $row['admission_number'] ?? '' }}">
                                  <i class="bi bi-search"></i> Search Student
                                </button>
                                <label class="form-check-label small">
                                  <input type="checkbox" name="skip_rows[{{ $index }}]" value="1" class="form-check-input skip-row-checkbox" onchange="updateSkipRow({{ $index }})">
                                  Skip this row
                                </label>
                              </div>
                              <input type="hidden" name="student_matches[{{ $index }}]" class="selected-student-id" value="{{ $row['student_id'] ?? '' }}" data-skip-row="{{ $index }}">
                            @elseif($needsConfirmation)
                              <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="confirmations[{{ $index }}]" id="use_new_{{ $index }}" value="use_new" checked>
                                <label class="btn btn-outline-success" for="use_new_{{ $index }}">Use New</label>
                                
                                <input type="radio" class="btn-check" name="confirmations[{{ $index }}]" id="keep_existing_{{ $index }}" value="keep_existing">
                                <label class="btn btn-outline-secondary" for="keep_existing_{{ $index }}">Keep Existing</label>
                              </div>
                            @else
                              <span class="text-muted">—</span>
                            @endif
                          </td>
                        </tr>
                        @php
                          $row['row_index'] = $index;
                        @endphp
                        <input type="hidden" name="rows[]" value="{{ base64_encode(json_encode($row)) }}">
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif
          @endforeach
        </div>

        @if($missingDropOffs->count())
          <div class="alert alert-warning mb-4">
            <div class="fw-semibold mb-3">
              <i class="bi bi-map"></i> New Drop-off Points Detected
            </div>
            <p class="small mb-3">Choose whether to create a new drop-off point or map to an existing one.</p>
            <div class="row g-3">
              @foreach($missingDropOffs as $name)
                <div class="col-md-6 col-lg-4">
                  <label class="form-label small fw-semibold mb-1">{{ $name }}</label>
                  <select name="dropoff_map[{{ \Illuminate\Support\Str::lower($name) }}]" class="form-select">
                    <option value="create">Create &amp; use "{{ $name }}"</option>
                    @foreach($dropOffPoints as $point)
                      <option value="{{ $point->id }}">{{ $point->name }}</option>
                    @endforeach
                  </select>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        <div class="d-flex gap-3 pt-3 border-top">
          <a href="{{ route('finance.transport-fees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> Cancel
          </a>
          <button type="submit" class="btn btn-finance btn-finance-primary" 
            @if($needsMatchingCount > 0 || $missingStudentCount > 0) 
              id="submitBtn" 
              disabled 
            @endif>
            <i class="bi bi-check2-circle"></i> Apply Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  @include('partials.student_search_modal')

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('importForm');
      const submitBtn = document.getElementById('submitBtn');
      let currentSearchIndex = null;
      
      // Handle student search for missing students
      document.querySelectorAll('.search-student-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          currentSearchIndex = this.dataset.index;
          const studentName = this.dataset.studentName || '';
          const admission = this.dataset.admission || '';
          
          // Pre-fill search input with student name or admission
          const searchInput = document.getElementById('studentSearchInput');
          if (searchInput) {
            searchInput.value = admission || studentName;
            searchInput.dispatchEvent(new Event('input'));
          }
          
          // Show modal
          const modal = new bootstrap.Modal(document.getElementById('studentSearchModal'));
          modal.show();
        });
      });
      
      // Handle student selection from modal
      function handleStudentSelection(event) {
        if (currentSearchIndex !== null) {
          const student = event.detail;
          // Try multiple methods to find the hidden input
          let hiddenInput = document.querySelector(`input[name="student_matches[${currentSearchIndex}]"]`);
          if (!hiddenInput) {
            // Fallback: find by class within the same row
            const btn = document.querySelector(`.search-student-btn[data-index="${currentSearchIndex}"]`);
            if (btn) {
              const row = btn.closest('tr');
              if (row) {
                hiddenInput = row.querySelector('input.selected-student-id');
              }
            }
          }
          
          const btn = document.querySelector(`.search-student-btn[data-index="${currentSearchIndex}"]`);
          
          if (hiddenInput) {
            hiddenInput.value = student.id;
            hiddenInput.removeAttribute('required'); // Remove required after selection
          }
          
          if (btn) {
            btn.innerHTML = `<i class="bi bi-check-circle"></i> ${student.name} (${student.adm})`;
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-success');
            btn.disabled = true;
          }
          
          checkIfReady();
          currentSearchIndex = null;
        }
      }
      
      // Listen on both window and document for compatibility
      window.addEventListener('studentSelected', handleStudentSelection);
      document.addEventListener('studentSelected', handleStudentSelection);
      
      // Check if all required fields are filled
      function checkIfReady() {
        if (!submitBtn) return;
        
        let allFilled = true;
        
        // Check student match selects
        const studentMatchSelects = form.querySelectorAll('select.student-match-select');
        studentMatchSelects.forEach(select => {
          if (!select.value) {
            allFilled = false;
          }
        });
        
        // Check hidden student inputs (for missing students)
        const studentHiddenInputs = form.querySelectorAll('input.selected-student-id[required]');
        studentHiddenInputs.forEach(input => {
          if (!input.value) {
            allFilled = false;
          }
        });
        
        if (allFilled) {
          submitBtn.disabled = false;
        } else {
          submitBtn.disabled = true;
        }
      }
      
      if (submitBtn && submitBtn.disabled) {
        // Check if all student matches are filled
        const studentMatchSelects = form.querySelectorAll('select.student-match-select');
        
        studentMatchSelects.forEach(select => {
          select.addEventListener('change', function() {
            checkIfReady();
          });
        });
        
        // Also check on page load
        checkIfReady();
      }
    });
  </script>
@endsection
