@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'AI Content Generator', 'icon' => 'bi bi-robot', 'subtitle' => 'Royal Kings tone — warm, Christian, family-centered'])
<div class="settings-card mb-4"><div class="card-body">
<form id="aiForm" class="row g-3">
<div class="col-md-4"><label class="form-label">Type</label><select name="content_type" class="form-select">@foreach(\App\Models\Website\AiContentLog::TYPES as $t)<option value="{{ $t }}">{{ str_replace('_',' ', $t) }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Subject / topic</label><input name="subject" class="form-control" required placeholder="e.g. Term 2 opening day"></div>
<div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-settings-primary w-100">Generate</button></div>
</form>
<div id="aiResult" class="mt-3 small text-muted"></div>
</div></div>
<div class="settings-card"><div class="card-header">Recent generations</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Type</th><th>Status</th><th>User</th><th>When</th></tr></thead><tbody>
@foreach($logs as $log)<tr><td>{{ $log->content_type }}</td><td><span class="badge bg-{{ $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'danger' : 'secondary') }}">{{ $log->status }}</span></td><td>{{ $log->user?->name ?? '—' }}</td><td>{{ $log->created_at->diffForHumans() }}</td></tr>@endforeach
</tbody></table></div><div class="card-footer">{{ $logs->links() }}</div></div>
</div></div>
@push('scripts')
<script>
document.getElementById('aiForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch('{{ route('website.ai.generate') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify(Object.fromEntries(fd)) });
  const json = await res.json();
  document.getElementById('aiResult').textContent = json.message || 'Queued — refresh when status is completed.';
});
</script>
@endpush
@endsection
