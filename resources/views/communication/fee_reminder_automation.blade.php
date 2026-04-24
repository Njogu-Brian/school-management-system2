@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-3">
        <div class="crumb">Communication</div>
        <h2 class="mb-1">Fee reminder automation</h2>
        <p class="text-muted mb-0">Configure when to email, SMS, and WhatsApp parents for invoice due dates, payment-plan installments, and <strong>fee clearance deadlines</strong>. Message text uses <a href="{{ route('communication-templates.index') }}">Communication Templates</a> (including per-status clearance templates).</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-info small">
        The scheduler should run every minute (e.g. <code>php artisan schedule:run</code> in cron). Reminders fire once per day at the time you set below. Ensure <strong>fee clearance</strong> snapshots stay current (they recompute when invoices, payments, or payment plans change, and nightly via <code>fee-clearance:recompute</code>).
    </div>

    <form method="POST" action="{{ route('communication.fee-reminder-automation.update') }}" class="card shadow-sm border-0">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1" {{ $settings->enabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="enabled">Enable automatic fee reminders</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Daily send time (server)</label>
                    <input type="time" name="send_time" class="form-control" value="{{ $settings->sendTime }}" required>
                </div>
            </div>

            <h5 class="border-bottom pb-2 mb-3">Invoices &amp; payment plan installments</h5>
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label class="form-label">Days <em>before</em> due date (comma or space separated)</label>
                    <input type="text" name="days_before_due" class="form-control" value="{{ implode(', ', $settings->daysBeforeDue) }}" placeholder="7, 3, 1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Days <em>after</em> overdue installment (comma separated)</label>
                    <input type="text" name="days_after_overdue" class="form-control" value="{{ implode(', ', $settings->daysAfterOverdue) }}" placeholder="1, 3, 7">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Channels — before due</label>
                    @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels_before_due[]" value="{{ $val }}" id="cb_{{ $val }}_b" {{ in_array($val, $settings->channelsBeforeDue, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cb_{{ $val }}_b">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    <label class="form-label">Channels — on due date</label>
                    @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels_on_due[]" value="{{ $val }}" id="cb_{{ $val }}_o" {{ in_array($val, $settings->channelsOnDue, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cb_{{ $val }}_o">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    <label class="form-label">Channels — after overdue</label>
                    @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels_after_overdue[]" value="{{ $val }}" id="cb_{{ $val }}_a" {{ in_array($val, $settings->channelsAfterOverdue, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cb_{{ $val }}_a">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <h5 class="border-bottom pb-2 mb-3">Fee clearance deadline (term threshold)</h5>
            <p class="small text-muted">Targets parents of students with <strong>pending</strong> clearance and a computed <code>final_clearance_deadline</code>. Uses templates named <code>fee_clearance_reminder_<em>reason</em>_<em>sms|email|whatsapp</em></code> when present (e.g. <code>fee_clearance_reminder_below_threshold_sms</code>), otherwise the general finance fee reminder templates.</p>
            <div class="row g-3 mb-2">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="clearance_enabled" id="clearance_enabled" value="1" {{ $settings->clearanceEnabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="clearance_enabled">Send clearance deadline reminders</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Days <em>before</em> clearance deadline</label>
                    <input type="text" name="clearance_days_before" class="form-control" value="{{ implode(', ', $settings->clearanceDaysBefore) }}" placeholder="2, 1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Days <em>after</em> deadline (still pending)</label>
                    <input type="text" name="clearance_days_after" class="form-control" value="{{ implode(', ', $settings->clearanceDaysAfter) }}" placeholder="1, 3">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Clearance — before</label>
                    @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="clearance_channels_before[]" value="{{ $val }}" id="ccb_{{ $val }}_b" {{ in_array($val, $settings->clearanceChannelsBefore, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="ccb_{{ $val }}_b">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    <label class="form-label">Clearance — on deadline</label>
                    @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="clearance_channels_on[]" value="{{ $val }}" id="ccb_{{ $val }}_o" {{ in_array($val, $settings->clearanceChannelsOn, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="ccb_{{ $val }}_o">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    <label class="form-label">Clearance — after deadline</label>
                    @foreach(['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="clearance_channels_after[]" value="{{ $val }}" id="ccb_{{ $val }}_a" {{ in_array($val, $settings->clearanceChannelsAfter, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="ccb_{{ $val }}_a">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save settings</button>
            <a href="{{ route('finance.fee-reminders.index') }}" class="btn btn-outline-secondary ms-2">Fee reminders log</a>
        </div>
    </form>
</div>
@endsection
