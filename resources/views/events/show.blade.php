@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
@php
    $canManage = !auth()->user()->hasRole('Teacher') && !auth()->user()->hasRole('teacher');
@endphp
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Events / Details</div>
                <h1>{{ $event->title }}</h1>
                <p>{{ $event->description ?: 'No description provided.' }}</p>
                <div class="d-flex gap-2 mt-2 flex-wrap">
                    <span class="settings-chip"><i class="bi bi-tag"></i> {{ ucfirst($event->type) }}</span>
                    <span class="settings-chip"><i class="bi bi-eye"></i> {{ ucfirst($event->visibility) }}</span>
                    @if($event->academicYear)
                        <span class="settings-chip"><i class="bi bi-calendar3"></i> {{ $event->academicYear->year }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if($canManage)
                    <a href="{{ route('events.edit', $event) }}" class="btn btn-ghost-strong">
                        <i class="bi bi-pencil-square"></i> Edit
                    </a>
                    <form action="{{ route('events.destroy', $event) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this event?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                @endif
                <a href="{{ route('events.index') }}" class="btn btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back to Calendar
                </a>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Event Overview</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mini-stat">
                            <i class="bi bi-calendar-event"></i>
                            <div>
                                <div class="fw-semibold">Dates</div>
                                <div class="form-note">
                                    {{ $event->start_date->format('M d, Y') }}
                                    @if($event->end_date)
                                        – {{ $event->end_date->format('M d, Y') }}
                                    @endif
                                    @if(!$event->is_all_day && $event->start_time)
                                        <br>
                                        {{ date('H:i', strtotime($event->start_time)) }}
                                        @if($event->end_time)
                                            – {{ date('H:i', strtotime($event->end_time)) }}
                                        @endif
                                    @else
                                        <br>All day
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mini-stat">
                            <i class="bi bi-geo-alt"></i>
                            <div>
                                <div class="fw-semibold">Venue</div>
                                <div class="form-note">{{ $event->venue ?: 'Not specified' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mini-stat">
                            <i class="bi bi-eye"></i>
                            <div>
                                <div class="fw-semibold">Visibility</div>
                                <div class="form-note">{{ ucfirst($event->visibility) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mini-stat">
                            <i class="bi bi-award"></i>
                            <div>
                                <div class="fw-semibold">Type</div>
                                <div class="form-note">{{ ucfirst($event->type) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection