@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="crumb">Settings / Academics</div>
                <h1>Academic &amp; Report Settings</h1>
                <p>Control how report cards behave for parents and how exam analytics use pass marks.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="settings-chip"><i class="bi bi-shield-check"></i> Admin access only</span>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <a href="{{ route('settings.index') }}" class="btn btn-ghost-strong btn-sm text-nowrap">
                    <i class="bi bi-arrow-left"></i> Back to System Settings
                </a>
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-body">
                <h2 class="h5 mb-3">Report cards &amp; parents</h2>
                <form method="POST" action="{{ route('settings.academic-reports.update') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="notify_parents_on_report_publish" name="notify_parents_on_report_publish" value="1"
                                {{ old('notify_parents_on_report_publish', $notifyParentsOnReportPublish) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_parents_on_report_publish">
                                Notify parents (SMS) when a report card is published
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Sends the public report card link when publishing, if SMS is configured.</small>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="block_results_when_fee_balance" name="block_results_when_fee_balance" value="1"
                                {{ old('block_results_when_fee_balance', $blockResultsWhenFeeBalance) ? 'checked' : '' }}>
                            <label class="form-check-label" for="block_results_when_fee_balance">
                                Block public report card when there is an outstanding fee balance for that term
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="exam_pass_mark_percent" class="form-label">Exam pass mark (%)</label>
                        <input type="number" min="0" max="100" class="form-control" id="exam_pass_mark_percent" name="exam_pass_mark_percent"
                            value="{{ old('exam_pass_mark_percent', $examPassMarkPercent) }}" required>
                        <small class="text-muted">Used for pass rates and analytics in exam reports.</small>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-settings-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
