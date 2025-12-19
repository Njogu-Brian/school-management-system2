@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Create Template',
            'icon' => 'bi bi-plus-circle',
            'subtitle' => 'Build a reusable email/SMS template',
            'actions' => '<a href="' . route('communication-templates.index') . '" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>'
        ])

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('communication-templates.store') }}" method="POST">
                    @csrf
                    @include('communication.templates.partials.form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

