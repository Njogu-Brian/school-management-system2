@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Report Card Skills</div>
        <h1 class="mb-1">Skills - {{ $reportCard->student->full_name }}</h1>
        <p class="text-muted mb-0">Manage skill ratings for this report card.</p>
      </div>
      <a href="{{ route('academics.report_cards.skills.create',$reportCard) }}" class="btn btn-settings-primary"><i class="bi bi-plus"></i> Add Skill</a>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Skill</th>
                <th>Rating</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($skills as $skill)
              <tr>
                <td class="fw-semibold">{{ $skill->skill_name }}</td>
                <td><span class="pill-badge pill-info">{{ $skill->rating }}</span></td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <a href="{{ route('academics.report_cards.skills.edit',[$reportCard,$skill]) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('academics.report_cards.skills.destroy',[$reportCard,$skill]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete skill?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="3" class="text-center text-muted py-4">No skills assigned.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
