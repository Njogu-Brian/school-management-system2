@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Parent Details</h1>

    <h4>Father's Information</h4>
    <p><strong>Name:</strong> {{ $parent->father_name }}</p>
    <p><strong>Phone:</strong> {{ $parent->father_phone }}</p>
    <p><strong>WhatsApp:</strong> {{ $parent->father_whatsapp }}</p>
    <p><strong>Email:</strong> {{ $parent->father_email }}</p>
    <p><strong>ID Number:</strong> {{ $parent->father_id_number }}</p>

    <hr>

    <h4>Mother's Information</h4>
    <p><strong>Name:</strong> {{ $parent->mother_name }}</p>
    <p><strong>Phone:</strong> {{ $parent->mother_phone }}</p>
    <p><strong>WhatsApp:</strong> {{ $parent->mother_whatsapp }}</p>
    <p><strong>Email:</strong> {{ $parent->mother_email }}</p>
    <p><strong>ID Number:</strong> {{ $parent->mother_id_number }}</p>

    <hr>

    <h4>Guardian's Information</h4>
    <p><strong>Name:</strong> {{ $parent->guardian_name }}</p>
    <p><strong>Phone:</strong> {{ $parent->guardian_phone }}</p>
    <p><strong>WhatsApp:</strong> {{ $parent->guardian_whatsapp }}</p>
    <p><strong>Email:</strong> {{ $parent->guardian_email }}</p>
    <p><strong>ID Number:</strong> {{ $parent->guardian_id_number }}</p>

    <hr>

    <h4>Linked Students</h4>
    @if($parent->students->count() > 0)
        <ul>
            @foreach ($parent->students as $student)
                <li>{{ $student->name }} (Class: {{ $student->class }})</li>
            @endforeach
        </ul>
    @else
        <p>No students linked to this parent.</p>
    @endif

    <a href="{{ route('parents.index') }}" class="btn btn-secondary">Back to List</a>
</div>
@endsection
