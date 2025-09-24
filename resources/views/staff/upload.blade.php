@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Bulk Upload Staff</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('errors'))
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach(session('errors') as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.upload.handle') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Upload Excel File *</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
        </div>
        <button type="submit" class="btn btn-primary">⬆ Upload</button>
        <a href="{{ route('staff.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
