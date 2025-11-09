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
        <form action="{{ route('students.restore', $student->id) }}" method="POST" onsubmit="return confirm('Restore this student?')">
          @csrf
          <button class="dropdown-item" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i> Restore</button>
        </form>
      </li>
    @else
      <li>
        <form action="{{ route('students.archive', $student->id) }}" method="POST" onsubmit="return confirm('Archive this student?')">
          @csrf
          <button class="dropdown-item" type="submit"><i class="bi bi-archive me-1"></i> Archive</button>
        </form>
      </li>
    @endif
  </ul>
</div>
