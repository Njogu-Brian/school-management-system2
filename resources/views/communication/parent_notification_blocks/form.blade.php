@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => $parent ? 'Edit parent notification preference' : 'Add parent notification preference',
            'subtitle' => 'Automated school messages go to father and mother contacts only (not guardian). You may exclude one parent if the other has at least one phone, WhatsApp, or email.',
            'icon' => 'bi bi-person-slash',
            'actions' => '<a href="' . route('communication.parent-notification-blocks.index') . '" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>'
        ])

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-body">
                @if($parent)
                    <form method="post" action="{{ route('communication.parent-notification-blocks.update', $parent) }}">
                        @csrf
                        @method('PUT')

                        @if($student)
                            <p class="mb-3"><strong>Student:</strong> {{ $student->full_name }} ({{ $student->admission_number }})</p>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Do not send automated school notifications to</label>
                            <select name="school_notifications_muted_parent" class="form-select" style="max-width:420px">
                                <option value="" @selected(old('school_notifications_muted_parent', $parent->school_notifications_muted_parent) === null || old('school_notifications_muted_parent', $parent->school_notifications_muted_parent) === '')>Both parents (normal)</option>
                                <option value="father" @selected(old('school_notifications_muted_parent', $parent->school_notifications_muted_parent) === 'father')>Father only (mother receives)</option>
                                <option value="mother" @selected(old('school_notifications_muted_parent', $parent->school_notifications_muted_parent) === 'mother')>Mother only (father receives)</option>
                            </select>
                            <div class="form-text">Choosing &quot;Both parents&quot; clears this preference.</div>
                        </div>

                        <button type="submit" class="btn btn-settings-primary">Save</button>
                    </form>
                @else
                    <form method="post" action="{{ route('communication.parent-notification-blocks.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="number" name="student_id" class="form-control" style="max-width:280px" value="{{ old('student_id') }}" required min="1">
                            <div class="form-text">Open a student profile; the numeric ID appears in the URL or browser bar.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Exclude from school notifications <span class="text-danger">*</span></label>
                            <select name="school_notifications_muted_parent" class="form-select" style="max-width:420px" required>
                                <option value="" disabled @selected(!old('school_notifications_muted_parent'))>Select…</option>
                                <option value="father" @selected(old('school_notifications_muted_parent') === 'father')>Father (mother receives)</option>
                                <option value="mother" @selected(old('school_notifications_muted_parent') === 'mother')>Mother (father receives)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-settings-primary">Save</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
