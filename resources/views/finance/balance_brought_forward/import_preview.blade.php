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
        <form method="POST" action="{{ route('finance.balance-brought-forward.import.commit') }}">
          @csrf
          
          <div class="finance-table-wrapper mb-3">
            <div class="table-responsive">
              <table class="finance-table align-middle">
                <thead>
                  <tr>
                    <th>Student</th>
                    <th>Admission #</th>
                    <th class="text-end">System Balance</th>
                    <th class="text-end">Import Balance</th>
                    <th class="text-end">Difference</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($preview as $row)
                    @php
                      $isIssue = $row['status'] !== 'ok';
                      $rowClass = match($row['status']) {
                        'student_not_found' => 'table-danger',
                        'exists_in_system_only' => 'table-warning',
                        'exists_in_import_only' => 'table-info',
                        'amount_differs' => 'table-warning',
                        default => ''
                      };
                    @endphp
                    <tr class="{{ $rowClass }}">
                      <td>
                        @if($row['student_id'])
                          <div class="fw-semibold">{{ $row['student_name'] }}</div>
                        @else
                          <div class="text-muted">{{ $row['student_name'] ?? '—' }}</div>
                        @endif
                      </td>
                      <td>{{ $row['admission_number'] }}</td>
                      <td class="text-end">
                        @if($row['system_balance'] !== null)
                          <strong>KES {{ number_format($row['system_balance'], 2) }}</strong>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="text-end">
                        @if($row['import_balance'] !== null)
                          <strong>KES {{ number_format($row['import_balance'], 2) }}</strong>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="text-end">
                        @if($row['difference'] !== null)
                          @if($row['difference'] > 0)
                            <span class="text-success">+KES {{ number_format($row['difference'], 2) }}</span>
                          @elseif($row['difference'] < 0)
                            <span class="text-danger">KES {{ number_format($row['difference'], 2) }}</span>
                          @else
                            <span class="text-muted">—</span>
                          @endif
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
                    @if($row['student_id'] && $row['import_balance'] !== null && $row['import_balance'] > 0)
                      <input type="hidden" name="rows[]" value="{{ base64_encode(json_encode([
                        'student_id' => $row['student_id'],
                        'admission_number' => $row['admission_number'],
                        'import_balance' => $row['import_balance'],
                      ])) }}">
                    @endif
                  @endforeach
                </tbody>
                @if(count($preview) > 0)
                <tfoot>
                  <tr>
                    <th colspan="2" class="text-end">Totals</th>
                    <th class="text-end">
                      KES {{ number_format(collect($preview)->where('system_balance', '!=', null)->sum('system_balance'), 2) }}
                    </th>
                    <th class="text-end">
                      KES {{ number_format(collect($preview)->where('import_balance', '!=', null)->sum('import_balance'), 2) }}
                    </th>
                    <th class="text-end">
                      @php
                        $totalDiff = collect($preview)->sum('difference') ?? 0;
                      @endphp
                      @if($totalDiff != 0)
                        <span class="{{ $totalDiff > 0 ? 'text-success' : 'text-danger' }}">
                          {{ $totalDiff > 0 ? '+' : '' }}KES {{ number_format($totalDiff, 2) }}
                        </span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </th>
                    <th></th>
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
            @if($hasIssues)
              <div class="alert alert-warning mb-0 d-flex align-items-center gap-2 flex-grow-1">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Please review issues above. Only rows with valid students and import balances will be processed.</span>
              </div>
            @endif
            <button type="submit" class="btn btn-finance btn-finance-primary" @if($hasIssues && collect($preview)->where('student_id')->where('import_balance', '>', 0)->count() == 0) disabled @endif>
              <i class="bi bi-check2-circle"></i> Commit Import
            </button>
          </div>
        </form>
      </div>
    </div>

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

