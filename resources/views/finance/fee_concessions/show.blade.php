@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3 d-flex justify-content-between align-items-center p-3">
        <h1 class="h4 mb-0">Fee Concession Details</h1>
        <div class="d-flex gap-2">
            @if(!$feeConcession->is_active)
                <form action="{{ route('finance.fee-concessions.approve', $feeConcession) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-finance btn-finance-success">Approve</button>
                </form>
            @else
                <form action="{{ route('finance.fee-concessions.deactivate', $feeConcession) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-finance btn-finance-warning">Deactivate</button>
                </form>
            @endif
            <a href="{{ route('finance.fee-concessions.index') }}" class="btn btn-finance btn-finance-outline">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="finance-card finance-animate">
        <div class="finance-card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="200">Student:</th>
                    <td>{{ $feeConcession->student->full_name }}</td>
                </tr>
                <tr>
                    <th>Votehead:</th>
                    <td>{{ $feeConcession->votehead->name ?? 'All Voteheads' }}</td>
                </tr>
                <tr>
                    <th>Type:</th>
                    <td>{{ ucfirst($feeConcession->type) }}</td>
                </tr>
                <tr>
                    <th>Value:</th>
                    <td>
                        {{ $feeConcession->type == 'percentage' ? number_format($feeConcession->value, 1) . '%' : 'KES ' . number_format($feeConcession->value, 2) }}
                    </td>
                </tr>
                <tr>
                    <th>Reason:</th>
                    <td>{{ $feeConcession->reason }}</td>
                </tr>
                <tr>
                    <th>Description:</th>
                    <td>{{ $feeConcession->description ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Start Date:</th>
                    <td>{{ $feeConcession->start_date->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <th>End Date:</th>
                    <td>{{ $feeConcession->end_date ? $feeConcession->end_date->format('M d, Y') : 'No End Date' }}</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <span class="badge bg-{{ $feeConcession->is_active ? 'success' : 'secondary' }}">
                            {{ $feeConcession->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                </tr>
                @if($feeConcession->approver)
                <tr>
                    <th>Approved By:</th>
                    <td>{{ $feeConcession->approver->name ?? 'N/A' }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
  </div>
</div>
@endsection

