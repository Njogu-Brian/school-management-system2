@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Homework</h1>
        <p class="text-muted mb-0">Assign, track, and review homework.</p>
      </div>
      <a href="{{ route('academics.homework.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Assign Homework</a>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Title</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Due</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($homeworks as $task)
              <tr>
                <td class="fw-semibold">{{ $task->title }}</td>
                <td>{{ $task->classroom?->name ?? 'All' }}</td>
                <td>{{ $task->subject?->name ?? 'N/A' }}</td>
                <td><span class="pill-badge pill-info">{{ $task->due_date->format('d M Y') }}</span></td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1 flex-wrap">
                    <a href="{{ route('academics.homework.show',$task) }}" class="btn btn-sm btn-ghost-strong text-info"><i class="bi bi-eye"></i></a>
                    <form action="{{ route('academics.homework.destroy',$task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete homework?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              @endforeach
              @if($homeworks->isEmpty())
                <tr><td colspan="5" class="text-center text-muted py-4">No homework yet.</td></tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $homeworks->links() }}</div>
    </div>
  </div>
</div>
@endsection
