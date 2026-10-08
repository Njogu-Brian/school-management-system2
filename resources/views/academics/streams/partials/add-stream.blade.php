@php
  $availableStreams = $catalogStreams->reject(fn ($stream) => $streams->contains('id', $stream->id))->values();
@endphp
@if($availableStreams->isNotEmpty())
  <button type="button" class="add-stream-card text-center" data-bs-toggle="modal" data-bs-target="#addStreamModal{{ $classroom->id }}">
    <span><i class="bi bi-plus-lg"></i><br><small>Add Stream</small></span>
  </button>

  <div class="modal fade" id="addStreamModal{{ $classroom->id }}" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content settings-card mb-0">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title">Add stream to {{ $classroom->name }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="{{ route('academics.classrooms.assign-stream', $classroom) }}" method="POST">
          @csrf
          <div class="modal-body">
            <p class="text-muted small">Pick a stream that already exists. This does not create a new class.</p>
            <div class="mb-3">
              <label class="form-label">Stream <span class="text-danger">*</span></label>
              <select name="stream_id" class="form-select" required>
                <option value="">-- Select stream --</option>
                @foreach($availableStreams as $stream)
                  <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Class teacher</label>
              <select name="class_teacher_staff_id" class="form-select">
                <option value="">— No Class Teacher —</option>
                @foreach($staffTeachers as $st)
                  <option value="{{ $st->id }}">{{ $st->full_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Assistant class teacher</label>
              <select name="assistant_teacher_staff_id" class="form-select">
                <option value="">— No Assistant —</option>
                @foreach($staffTeachers as $st)
                  <option value="{{ $st->id }}">{{ $st->full_name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-settings-primary">Assign Stream</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif
