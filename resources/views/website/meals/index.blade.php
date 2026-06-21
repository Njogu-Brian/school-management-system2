@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Weekly Lunch Menu', 'icon' => 'bi bi-cup-hot', 'subtitle' => 'Live operations'])
<div class="settings-card mb-4"><div class="card-body">
<form method="POST" action="{{ route('website.meals.store') }}" class="row g-2">@csrf
<div class="col-md-3"><input type="date" name="meal_date" class="form-control" required></div>
<div class="col-md-3"><input name="breakfast" class="form-control" placeholder="Breakfast"></div>
<div class="col-md-3"><input name="lunch" class="form-control" placeholder="Lunch"></div>
<div class="col-md-2"><input name="snack" class="form-control" placeholder="Snack"></div>
<div class="col-md-1"><button class="btn btn-settings-primary w-100">Save</button></div>
</form></div></div>
<div class="settings-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Breakfast</th><th>Lunch</th><th>Snack</th></tr></thead><tbody>
@foreach($meals as $m)<tr><td>{{ $m->meal_date->format('D, d M') }}</td><td>{{ $m->breakfast }}</td><td>{{ $m->lunch }}</td><td>{{ $m->snack }}</td></tr>@endforeach
</tbody></table></div><div class="card-footer">{{ $meals->links() }}</div></div>
</div></div>
@endsection
