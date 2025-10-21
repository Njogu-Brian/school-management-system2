<div class="tab-pane fade show" id="placeholders" role="tabpanel">
    <h5 class="mb-3">📍 Communication Placeholders</h5>

    <form method="POST" action="{{ route('settings.placeholders.store') }}" class="row g-3 mb-4">
        @csrf
        <div class="col-md-5">
            <input type="text" name="key" class="form-control" placeholder="e.g. principal_name" required>
        </div>
        <div class="col-md-5">
            <input type="text" name="value" class="form-control" placeholder="e.g. Mr. Brian Murathime" required>
        </div>
        <div class="col-md-2">
            <button class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> Add</button>
        </div>
    </form>

    <h6 class="mt-4">Built-in Placeholders</h6>
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Placeholder</th>
                <th>Example Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach($systemPlaceholders as $i => $ph)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td><code>{{ '{' . $ph['key'] . '}' }}</code></td>
                    <td>{{ $ph['value'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h6 class="mt-4">Custom Placeholders</h6>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Placeholder</th>
                <th>Value</th>
                <th>Example Usage</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customPlaceholders as $i => $p)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td><code>{{ '{' . $p->key . '}' }}</code></td>
                    <td>{{ $p->value }}</td>
                    <td>{{ '{' . $p->key . '}' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">No custom placeholders added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
