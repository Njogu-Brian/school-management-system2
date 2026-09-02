@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        .req-kpi { border-radius: 12px; padding: 1rem 1.25rem; }
        .req-kpi h2 { margin: 0; font-size: 1.75rem; }
        .learner-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem; margin-bottom: .75rem; }
        .item-chip { display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 999px; padding: .15rem .6rem; margin: .15rem .2rem 0 0; font-size: .85rem; }
        @media print {
            .no-print, .sidebar, .topbar, .page-header .btn, form, .crumb { display: none !important; }
            .learner-card { break-inside: avoid; }
        }
    </style>
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Requirements</div>
                <h1>Requirements fulfilment</h1>
                <p>Who has brought everything, who has brought some, and who has brought nothing — plus what is still expected.</p>
            </div>
            <div class="d-flex gap-2 no-print">
                <a class="btn btn-ghost-strong" href="{{ route('inventory.reports.requirements.csv', request()->query()) }}"><i class="bi bi-download"></i> CSV</a>
                <button class="btn btn-settings-primary" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Academic year</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">Current</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" @selected($academic_year_id == $year->id)>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Term</label>
                        <select name="term_id" class="form-select">
                            <option value="">Current</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" @selected($term_id == $term->id)>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class</label>
                        <select name="classroom_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All classes</option>
                            @foreach($report['classrooms'] as $classroom)
                                <option value="{{ $classroom->id }}" @selected($classroom_id == $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stream</label>
                        <select name="stream_id" class="form-select">
                            <option value="">All streams</option>
                            @foreach($report['streams'] as $stream)
                                <option value="{{ $stream->id }}" @selected($stream_id == $stream->id)>{{ $stream->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-settings-primary" type="submit"><i class="bi bi-funnel"></i> Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="req-kpi" style="background:#ecfdf5; color:#065f46;">
                    <div class="small fw-semibold">Fully brought</div>
                    <h2>{{ $report['summary']['complete'] }}</h2>
                    <div class="small">Learners with every item</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="req-kpi" style="background:#fff7ed; color:#9a3412;">
                    <div class="small fw-semibold">Partial</div>
                    <h2>{{ $report['summary']['partial'] }}</h2>
                    <div class="small">Brought some, still missing others</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="req-kpi" style="background:#fef2f2; color:#991b1b;">
                    <div class="small fw-semibold">Nothing brought</div>
                    <h2>{{ $report['summary']['none'] }}</h2>
                    <div class="small">Still expected to bring the list below</div>
                </div>
            </div>
        </div>

        @php
            $sections = [
                'complete' => ['title' => 'Fully brought', 'empty' => 'No learners have completed every requirement yet.', 'show_brought' => true, 'show_expected' => false],
                'partial' => ['title' => 'Partial', 'empty' => 'No learners are currently partial.', 'show_brought' => true, 'show_expected' => true],
                'none' => ['title' => 'Nothing brought', 'empty' => 'Every learner has brought at least one item.', 'show_brought' => false, 'show_expected' => true],
            ];
        @endphp

        @foreach($sections as $key => $meta)
            <div class="settings-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $meta['title'] }} <span class="input-chip">{{ count($report[$key]) }}</span></h5>
                </div>
                <div class="card-body">
                    @forelse($report[$key] as $learner)
                        <div class="learner-card">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $learner['name'] }}</div>
                                    <div class="text-muted small">{{ $learner['admission_number'] }} · {{ $learner['class_name'] }} @if($learner['stream_name'] !== '—')· {{ $learner['stream_name'] }}@endif</div>
                                </div>
                                <div class="small text-muted">{{ $learner['complete_count'] }}/{{ $learner['total_count'] }} items complete</div>
                            </div>
                            @if($meta['show_brought'])
                                <div class="mt-2">
                                    <div class="small fw-semibold">What they brought</div>
                                    @forelse($learner['brought_items'] as $item)
                                        <span class="item-chip">{{ $item['name'] }}: {{ rtrim(rtrim(number_format($item['brought'], 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($item['expected'], 2), '0'), '.') }} {{ $item['unit'] }} · {{ $item['handling'] }}</span>
                                    @empty
                                        <span class="text-muted small">Nothing recorded yet.</span>
                                    @endforelse
                                </div>
                            @endif
                            @if($meta['show_expected'])
                                <div class="mt-2">
                                    <div class="small fw-semibold">Still expected</div>
                                    @forelse($learner['outstanding_items'] as $item)
                                        <span class="item-chip">{{ $item['name'] }}: {{ rtrim(rtrim(number_format($item['outstanding'], 2), '0'), '.') }} {{ $item['unit'] }} remaining (of {{ rtrim(rtrim(number_format($item['expected'], 2), '0'), '.') }})</span>
                                    @empty
                                        <span class="text-muted small">Nothing outstanding.</span>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ $meta['empty'] }}</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
