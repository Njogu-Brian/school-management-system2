<div class="tab-pane fade" id="placeholders" role="tabpanel" aria-labelledby="placeholders-tab">
    <h5 class="mb-3">🔖 Communication Placeholders</h5>
    <p class="text-muted mb-4">
        Manage custom placeholders that can be used in email and SMS templates.  
        Use placeholders like <code>{school_name}</code>, <code>{student_name}</code>, or create your own (e.g. <code>{principal_name}</code>).
    </p>

    <!-- Create Placeholder Form -->
    <form action="{{ route('settings.placeholders.store') }}" method="POST" class="row g-3 mb-4 border rounded p-3 bg-light">
        @csrf
        <div class="col-md-4">
            <label class="form-label">Placeholder Key *</label>
            <input type="text" name="key" class="form-control" placeholder="e.g. principal_name" required>
            <small class="text-muted">Use only letters, numbers, and underscores (appears as {principal_name})</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Placeholder Value</label>
            <input type="text" name="value" class="form-control" placeholder="e.g. Mr. Brian Murathime" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> Add</button>
        </div>
    </form>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- List of Placeholders -->
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Placeholder</th>
                    <th>Value</th>
                    <th>Example Usage</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse(\App\Models\CommunicationPlaceholder::orderBy('key')->get() as $ph)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><code>{{ '{'.$ph->key.'}' }}</code></td>
                        <td>{{ $ph->value }}</td>
                        <td><small>Use as <code>{{ '{'.$ph->key.'}' }}</code> in any message</small></td>
                        <td class="text-end">
                            <form action="{{ route('settings.placeholders.destroy', $ph) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this placeholder?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No custom placeholders added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="alert alert-info mt-3">
        <strong>Tip:</strong> These custom placeholders are available automatically in all Email and SMS templates.
    </div>
</div>
