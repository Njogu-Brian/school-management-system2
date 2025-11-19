@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Transport Details - {{ $student->first_name }} {{ $student->last_name }}</h2>
      <small class="text-muted">Complete transport information</small>
    </div>
    <a href="{{ route('teacher.transport.index') }}" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="row g-3">
    {{-- Student Information --}}
    <div class="col-md-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-person"></i> Student Information</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <strong>Name:</strong> {{ $student->first_name }} {{ $student->last_name }}
            </div>
            <div class="col-md-3">
              <strong>Admission #:</strong> <span class="badge bg-primary">{{ $student->admission_number }}</span>
            </div>
            <div class="col-md-3">
              <strong>Class:</strong> {{ $student->classroom->name ?? '—' }}
            </div>
            <div class="col-md-3">
              <strong>Stream:</strong> {{ $student->stream->name ?? '—' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Morning Trip --}}
    @if($student->assignments->first() && $student->assignments->first()->morningTrip)
      @php $assignment = $student->assignments->first(); @endphp
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-sunrise"></i> Morning Trip</h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr>
                <th width="150">Trip:</th>
                <td><span class="badge bg-info">{{ $assignment->morningTrip->name ?? '—' }}</span></td>
              </tr>
              <tr>
                <th>Drop-off Point:</th>
                <td>{{ $assignment->morningDropOffPoint->name ?? '—' }}</td>
              </tr>
              @if($assignment->morningTrip->vehicle)
                <tr>
                  <th>Vehicle:</th>
                  <td>
                    <span class="badge bg-success">{{ $assignment->morningTrip->vehicle->registration_number ?? '—' }}</span>
                    @if($assignment->morningTrip->vehicle->driver_name)
                      <small class="text-muted d-block">Driver: {{ $assignment->morningTrip->vehicle->driver_name }}</small>
                    @endif
                  </td>
                </tr>
              @endif
              @if($assignment->morningDropOffPoint && $assignment->morningDropOffPoint->route)
                <tr>
                  <th>Route:</th>
                  <td>{{ $assignment->morningDropOffPoint->route->name ?? '—' }}</td>
                </tr>
              @endif
            </table>
          </div>
        </div>
      </div>
    @endif

    {{-- Evening Trip --}}
    @if($student->assignments->first() && $student->assignments->first()->eveningTrip)
      @php $assignment = $student->assignments->first(); @endphp
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-sunset"></i> Evening Trip</h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr>
                <th width="150">Trip:</th>
                <td><span class="badge bg-warning text-dark">{{ $assignment->eveningTrip->name ?? '—' }}</span></td>
              </tr>
              <tr>
                <th>Drop-off Point:</th>
                <td>{{ $assignment->eveningDropOffPoint->name ?? '—' }}</td>
              </tr>
              @if($assignment->eveningTrip->vehicle)
                <tr>
                  <th>Vehicle:</th>
                  <td>
                    <span class="badge bg-success">{{ $assignment->eveningTrip->vehicle->registration_number ?? '—' }}</span>
                    @if($assignment->eveningTrip->vehicle->driver_name)
                      <small class="text-muted d-block">Driver: {{ $assignment->eveningTrip->vehicle->driver_name }}</small>
                    @endif
                  </td>
                </tr>
              @endif
              @if($assignment->eveningDropOffPoint && $assignment->eveningDropOffPoint->route)
                <tr>
                  <th>Route:</th>
                  <td>{{ $assignment->eveningDropOffPoint->route->name ?? '—' }}</td>
                </tr>
              @endif
            </table>
          </div>
        </div>
      </div>
    @endif

    {{-- Route Information --}}
    @if($student->route)
      <div class="col-md-12">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-signpost"></i> Route Information</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <strong>Route Name:</strong> {{ $student->route->name ?? '—' }}
              </div>
              <div class="col-md-4">
                <strong>Area:</strong> {{ $student->route->area ?? '—' }}
              </div>
              @if($student->route->vehicles->count() > 0)
                <div class="col-md-4">
                  <strong>Vehicles on Route:</strong>
                  @foreach($student->route->vehicles as $vehicle)
                    <span class="badge bg-success">{{ $vehicle->registration_number }}</span>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endif

    @if(!$student->assignments->first() && !$student->route)
      <div class="col-12">
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle"></i> No transport assignment found for this student.
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

