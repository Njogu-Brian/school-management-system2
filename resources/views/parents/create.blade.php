@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add Parent Information</h1>
    <form action="{{ route('parent-info.store') }}" method="POST">
        @csrf
        
        <h4>Father's Information</h4>
        <div class="mb-3">
            <label>Father Name</label>
            <input type="text" name="father_name" class="form-control">
        </div>
        <div class="mb-3">
            <label>Father Phone</label>
            <input type="text" name="father_phone" class="form-control">
        </div>
        <div class="mb-3">
            <label>Father WhatsApp</label>
            <input type="text" name="father_whatsapp" class="form-control">
        </div>
        <div class="mb-3">
            <label>Father Email</label>
            <input type="email" name="father_email" class="form-control">
        </div>
        <div class="mb-3">
            <label>Father ID Number</label>
            <input type="text" name="father_id_number" class="form-control">
        </div>

        <h4>Mother's Information</h4>
        <div class="mb-3">
            <label>Mother Name</label>
            <input type="text" name="mother_name" class="form-control">
        </div>
        <div class="mb-3">
            <label>Mother Phone</label>
            <input type="text" name="mother_phone" class="form-control">
        </div>
        <div class="mb-3">
            <label>Mother WhatsApp</label>
            <input type="text" name="mother_whatsapp" class="form-control">
        </div>
        <div class="mb-3">
            <label>Mother Email</label>
            <input type="email" name="mother_email" class="form-control">
        </div>
        <div class="mb-3">
            <label>Mother ID Number</label>
            <input type="text" name="mother_id_number" class="form-control">
        </div>

        <h4>Guardian's Information (Optional)</h4>
        <div class="mb-3">
            <label>Guardian Name</label>
            <input type="text" name="guardian_name" class="form-control">
        </div>
        <div class="mb-3">
            <label>Guardian Phone</label>
            <input type="text" name="guardian_phone" class="form-control">
        </div>
        <div class="mb-3">
            <label>Guardian WhatsApp</label>
            <input type="text" name="guardian_whatsapp" class="form-control">
        </div>
        <div class="mb-3">
            <label>Guardian Email</label>
            <input type="email" name="guardian_email" class="form-control">
        </div>
        <div class="mb-3">
            <label>Guardian ID Number</label>
            <input type="text" name="guardian_id_number" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">Add Parent Info</button>
    </form>
</div>
@endsection
