@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Optional Fee Import Preview',
      'icon' => 'bi bi-upload',
      'subtitle' => 'Validate rows before applying to invoices'
  ])

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
    <div class="finance-card-header d-flex align-items-center gap-2">
      <i class="bi bi-list-check"></i>
      <span>Preview</span>
    </div>
    <div class="finance-card-body p-4">
      @php
        $newCount = collect($preview)->where('change_type', 'new')->count();
        $existingCount = collect($preview)->where('change_type', 'existing')->count();
        $changedCount = collect($preview)->where('change_type', 'changed')->count();
        $removedCount = count($removals ?? []);
        $needsMatchingCount = collect($preview)->where('status', 'needs_matching')->count();
      @endphp

      <div class="alert alert-info mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <strong>Summary:</strong>
            @if($newCount > 0) <span class="badge bg-success">{{ $newCount }} New</span> @endif
            @if($changedCount > 0) <span class="badge bg-warning text-dark">{{ $changedCount }} Changed</span> @endif
            @if($existingCount > 0) <span class="badge bg-secondary">{{ $existingCount }} Already Billed</span> @endif
            @if($removedCount > 0) <span class="badge bg-danger">{{ $removedCount }} Will Be Removed</span> @endif
            @if($needsMatchingCount > 0) <span class="badge bg-warning text-dark">{{ $needsMatchingCount }} Need Matching</span> @endif
          </div>
          <div>
            <strong>Total Amount:</strong> <span class="fw-bold">{{ number_format($totalAmount, 2) }}</span>
          </div>
        </div>
      </div>

      @if($missingVoteheads->count())
        <div class="alert alert-danger mb-3">
          <div class="fw-semibold mb-2">Invalid voteheads detected</div>
          <p class="small mb-2">The following voteheads were not found or are not optional:</p>
          <ul class="mb-0">
            @foreach($missingVoteheads as $name)
              <li>{{ $name }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if($needsMatchingCount > 0)
        <div class="alert alert-warning mb-3">
          <div class="fw-semibold mb-2">Students Need Matching</div>
          <p class="small mb-0">Some students have multiple matches. Please select the correct student for each row below.</p>
        </div>
      @endif

      <form method="POST" action="{{ route('finance.optional-fees.import.commit') }}" id="importForm">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="term" value="{{ $term }}">

        @if($newCount > 0 || $changedCount > 0)
        <h5 class="mb-2">New & Changed Billings</h5>
        <div class="finance-table-wrapper mb-4">
          <div class="table-responsive">
            <table class="finance-table align-middle">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Admission #</th>
                  <th>Votehead Name</th>
                  <th class="text-end">Amount</th>
                  <th>Change Type</th>
                  <th>Status</th>
                  @if($needsMatchingCount > 0)
                  <th>Select Student</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @foreach($preview as $index => $row)
                  @php 
                    $isOk = in_array($row['status'] ?? '', ['ok', 'already_billed']);
                    $changeType = $row['change_type'] ?? 'new';
                    $needsMatching = ($row['status'] ?? '') === 'needs_matching';
                  @endphp
                  @if($changeType !== 'existing' || $needsMatching)
                  <tr class="{{ $isOk ? '' : 'table-warning' }}">
                    <td>{{ $row['student_name'] ?? '—' }}</td>
                    <td>{{ $row['admission_number'] ?? '—' }}</td>
                    <td>{{ $row['votehead_name'] ?? '—' }}</td>
                    <td class="text-end">
                      @if($row['existing_amount'] ?? null)
                        <div>
                          <span class="text-decoration-line-through text-muted">{{ number_format($row['existing_amount'], 2) }}</span>
                          <span class="ms-2">{{ number_format($row['amount'], 2) }}</span>
                        </div>
                      @else
                        {{ $row['amount'] !== null ? number_format($row['amount'], 2) : '—' }}
                      @endif
                    </td>
                    <td>
                      @if($changeType === 'new')
                        <span class="badge bg-success">New</span>
                      @elseif($changeType === 'changed')
                        <span class="badge bg-warning text-dark">Changed</span>
                      @elseif($changeType === 'existing')
                        <span class="badge bg-secondary">Existing</span>
                      @endif
                    </td>
                    <td>
                      @if($isOk && !$needsMatching)
                        <span class="badge bg-success">Ready</span>
                      @elseif($needsMatching)
                        <span class="badge bg-warning text-dark">Select Student</span>
                      @else
                        <span class="badge bg-warning text-dark">{{ $row['message'] ?? 'Needs attention' }}</span>
                      @endif
                    </td>
                    @if($needsMatchingCount > 0)
                    <td>
                      @if($needsMatching && !empty($row['matched_students']))
                        <select name="student_matches[{{ $index }}]" class="form-select form-select-sm" required>
                          <option value="">Select...</option>
                          @foreach($row['matched_students'] as $match)
                            <option value="{{ $match['id'] }}">{{ $match['name'] }} ({{ $match['admission_number'] }})</option>
                          @endforeach
                        </select>
                      @else
                        —
                      @endif
                    </td>
                    @endif
                  </tr>
                  @php
                    $row['row_index'] = $index;
                  @endphp
                  <input type="hidden" name="rows[]" value="{{ base64_encode(json_encode($row)) }}">
                  @endif
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        @endif

        @if($removedCount > 0)
        <h5 class="mb-2 text-danger">Removals (Previously Billed, Not in Import)</h5>
        <div class="finance-table-wrapper mb-4">
          <div class="table-responsive">
            <table class="finance-table align-middle">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Admission #</th>
                  <th>Votehead Name</th>
                  <th class="text-end">Amount</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($removals ?? [] as $removal)
                  <tr class="table-danger">
                    <td>{{ $removal['student_name'] ?? '—' }}</td>
                    <td>{{ $removal['admission_number'] ?? '—' }}</td>
                    <td>{{ $removal['votehead_name'] ?? '—' }}</td>
                    <td class="text-end">{{ number_format($removal['amount'], 2) }}</td>
                    <td>
                      <span class="badge bg-danger">Will Be Removed</span>
                    </td>
                  </tr>
                  <input type="hidden" name="removals[]" value="{{ base64_encode(json_encode($removal)) }}">
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        @endif

        <div class="d-flex gap-3">
          <a href="{{ route('finance.optional_fees.index') }}" class="btn btn-outline-secondary">
            Cancel
          </a>
          <button type="submit" class="btn btn-finance btn-finance-primary" 
            @if($missingVoteheads->count() || $needsMatchingCount > 0) 
              id="submitBtn" 
              disabled 
            @endif>
            <i class="bi bi-check2-circle"></i> Apply Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('importForm');
      const submitBtn = document.getElementById('submitBtn');
      
      if (submitBtn && submitBtn.disabled) {
        // Check if all student matches are filled
        const studentMatchSelects = form.querySelectorAll('select[name^="student_matches"]');
        let allMatched = true;
        
        studentMatchSelects.forEach(select => {
          if (!select.value) {
            allMatched = false;
          }
          select.addEventListener('change', function() {
            checkIfReady();
          });
        });
        
        function checkIfReady() {
          let allFilled = true;
          studentMatchSelects.forEach(select => {
            if (!select.value) {
              allFilled = false;
            }
          });
          
          if (allFilled && {{ $missingVoteheads->count() }} === 0) {
            submitBtn.disabled = false;
          } else {
            submitBtn.disabled = true;
          }
        }
      }
    });
  </script>
@endsection

