@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Create Announcement',
        'icon' => 'bi bi-plus-circle',
        'subtitle' => 'Create a new school announcement',
        'actions' => '<a href="' . route('announcements.index') . '" class="btn btn-comm btn-comm-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="comm-card comm-animate">
        <div class="comm-card-body">
            <form action="{{ route('announcements.store') }}" method="POST">
                @include('communication.announcements.partials._form')
            </form>
        </div>
    </div>
</div>
@endsection
