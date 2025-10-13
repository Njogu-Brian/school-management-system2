@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">⚙️ System Settings</h4>

    <ul class="nav nav-tabs" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">General Info</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="branding-tab" data-bs-toggle="tab" href="#branding" role="tab">Branding</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="regional-tab" data-bs-toggle="tab" href="#regional" role="tab">Regional Settings</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="ids-tab" data-bs-toggle="tab" href="#ids" role="tab">ID Settings</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="ids-tab" data-bs-toggle="tab" href="#features" role="tab">Features</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="placeholders-tab" data-bs-toggle="tab" href="#placeholders" role="tab">Placeholders</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="ids-tab" data-bs-toggle="tab" href="#modules" role="tab">Modules</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="system-tab" data-bs-toggle="tab" href="#system" role="tab">System Options</a>
        </li>
        
    </ul>

    <div class="tab-content p-4 border border-top-0 rounded-bottom" id="settingsTabContent">
        @include('settings.partials.general')
        @include('settings.partials.branding')
        @include('settings.partials.regional')
        @include('settings.partials.ids')
        @include('settings.partials.system')
        @include('settings.partials.features')
        @include('settings.partials.modules')
        @include('settings.partials.placeholders')
    </div>
</div>
@endsection
