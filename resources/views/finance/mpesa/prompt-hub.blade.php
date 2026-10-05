@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Mpesa Prompt',
        'icon' => 'bi bi-phone',
        'subtitle' => 'Dashboard, STK push prompts, and payment links',
        'actions' => '<a href="' . route('finance.mpesa.links.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-link-45deg"></i> Create Link</a>'
    ])

    @php
        $tab = $tab ?? request('tab', 'dashboard');
        if (!in_array($tab, ['dashboard', 'prompt', 'links'], true)) {
            $tab = 'dashboard';
        }
    @endphp

    <ul class="nav nav-pills mb-4 finance-animate" id="mpesaPromptTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'dashboard' ? 'active' : '' }}"
               href="{{ route('finance.mpesa.prompt', ['tab' => 'dashboard']) }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'prompt' ? 'active' : '' }}"
               href="{{ route('finance.mpesa.prompt', array_filter(['tab' => 'prompt', 'student_id' => request('student_id'), 'invoice_id' => request('invoice_id')])) }}">
                <i class="bi bi-phone-vibrate"></i> Prompt Parent
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'links' ? 'active' : '' }}"
               href="{{ route('finance.mpesa.prompt', ['tab' => 'links']) }}">
                <i class="bi bi-link-45deg"></i> Payment Links
            </a>
        </li>
    </ul>

    <div class="tab-content">
        @if($tab === 'dashboard')
            @include('finance.mpesa.partials.dashboard-tab')
        @elseif($tab === 'prompt')
            @include('finance.mpesa.partials.prompt-tab')
        @else
            @include('finance.mpesa.partials.links-tab')
        @endif
    </div>
  </div>
</div>
@endsection
