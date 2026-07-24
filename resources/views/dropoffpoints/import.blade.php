@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport / Drop-off points</p>
                <h1 class="mb-1">Import Drop-off Rates</h1>
                <p class="text-muted mb-0">Import point names with two-way and one-way fares. Student stops are managed separately.</p>
            </div>
            <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-body">
                <p class="mb-2">CSV/XLSX columns:</p>
                <ul class="mb-3">
                    <li><code>name</code> (required)</li>
                    <li><code>two_way_amount</code> (optional)</li>
                    <li><code>one_way_amount</code> (optional)</li>
                </ul>
                <a href="{{ route('transport.dropoffpoints.template') }}" class="btn btn-sm btn-ghost-strong">
                    <i class="bi bi-download"></i> Download template
                </a>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.dropoffpoints.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.txt" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary">Import</button>
                        <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
