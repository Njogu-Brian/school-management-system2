@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Edit Announcement',
        'icon' => 'bi bi-pencil-square',
        'subtitle' => 'Update announcement details',
        'actions' => '<a href="' . route('announcements.index') . '" class="btn btn-comm btn-comm-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="comm-card comm-animate">
        <div class="comm-card-body">
            <form action="{{ route('announcements.update', $announcement) }}" method="POST">
                @method('PUT')
                @include('communication.announcements.partials._form', ['announcement' => $announcement])
            </form>
        </div>
    </div>
</div>
@endsection
