<div class="btn-group">
  <a href="{{ route('students.show', $student->id) }}" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-eye"></i>
  </a>
  <a href="{{ route('students.edit', $student->id) }}" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-pencil-square"></i>
  </a>
  <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
    <span class="visually-hidden">Toggle Dropdown</span>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    @if ($student->archive)
      <li>
        <button class="dropdown-item restore-btn"
                data-student-id="{{ $student->id }}"
                data-student-name="{{ $student->full_name }}">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
        </button>
      </li>
      @push('archive-forms')
        <form id="restore-form-{{ $student->id }}" action="{{ route('students.restore', $student->id) }}" method="POST" class="d-none">
          @csrf
        </form>
      @endpush
    @else
      <li>
        <button class="dropdown-item archive-btn"
                type="button"
                data-student-id="{{ $student->id }}"
                data-student-name="{{ $student->full_name }}"
                data-bs-toggle="modal"
                data-bs-target="#archiveModal">
          <i class="bi bi-archive me-1"></i> Archive
        </button>
      </li>
    @endif
  </ul>
</div>
