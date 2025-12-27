@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Legacy Finance Imports',
        'icon' => 'bi bi-file-earmark-arrow-up',
        'subtitle' => 'Upload legacy PDF statements for a class and review parsed batches',
        'actions' => ''
    ])

    @if (session('success'))
        <div class="alert alert-success finance-animate">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger finance-animate">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <h5 class="mb-3 d-flex align-items-center">
            <i class="bi bi-upload me-2 text-primary"></i>
            Upload legacy statements (PDF)
        </h5>
        <form method="POST" action="{{ route('finance.legacy-imports.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-lg-6">
                <label for="pdf" class="finance-form-label">PDF file</label>
                <input type="file" name="pdf" id="pdf" class="finance-form-control" accept="application/pdf" required>
                <small class="text-muted">One PDF per class; multi-page students are supported.</small>
            </div>
            <div class="col-lg-4">
                <label for="class_label" class="finance-form-label">Class/Grade label (optional)</label>
                <input type="text" name="class_label" id="class_label" class="finance-form-control" placeholder="e.g. GRADE 1" value="{{ old('class_label') }}">
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import
                </button>
            </div>
        </form>
    </div>

    <div class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0">
        <div class="d-flex align-items-center justify-content-between px-3 pt-3">
            <h6 class="mb-0">Recent batches</h6>
        </div>
        <div class="table-responsive px-3 pb-3">
            <table class="finance-table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>File</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Students</th>
                        <th>Imported</th>
                        <th>Draft</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->id }}</td>
                            <td>{{ $batch->file_name }}</td>
                            <td>{{ $batch->class_label ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $batch->status === 'completed' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td>{{ $batch->total_students }}</td>
                            <td>{{ $batch->imported_students }}</td>
                            <td>
                                @if($batch->draft_students > 0)
                                    <span class="badge bg-warning text-dark">{{ $batch->draft_students }}</span>
                                @else
                                    {{ $batch->draft_students }}
                                @endif
                            </td>
                            <td>{{ $batch->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-finance btn-finance-ghost" href="{{ route('finance.legacy-imports.show', $batch) }}">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">📄</div>
                                    <div class="text-muted">No batches yet. Upload a PDF to see it here.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
            <div class="px-3 pb-3">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
@endsection

