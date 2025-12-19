@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Edit Template',
            'icon' => 'bi bi-pencil-square',
            'subtitle' => 'Update template details',
            'actions' => '<a href="' . route('communication-templates.index') . '" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>'
        ])

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('communication-templates.update', $template) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('communication.templates.partials.form', ['template' => $template])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

