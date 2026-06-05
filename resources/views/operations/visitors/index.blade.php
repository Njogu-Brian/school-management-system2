@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Operations</div>
                <h1>Visitor log</h1>
                <p>Track visitors on site. Currently on site: <strong>{{ $onSiteCount }}</strong></p>
            </div>
            <a href="{{ route('operations.visitors.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> Check in visitor
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Visitor</th>
                            <th>Purpose</th>
                            <th>Host</th>
                            <th>Checked in</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visitors as $visitor)
                            <tr>
                                <td>{{ $visitor->visitor_name }}</td>
                                <td>{{ $visitor->purpose ?? '—' }}</td>
                                <td>{{ $visitor->host_name ?? $visitor->hostStaff?->full_name ?? '—' }}</td>
                                <td>{{ $visitor->checked_in_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if($visitor->checked_out_at)
                                        <span class="badge bg-secondary">Checked out</span>
                                    @else
                                        <span class="badge bg-success">On site</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @unless($visitor->checked_out_at)
                                        <form method="POST" action="{{ route('operations.visitors.checkout', $visitor) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Check out</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No visitors logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $visitors->links() }}</div>
        </div>
    </div>
</div>
@endsection
