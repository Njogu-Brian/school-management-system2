@if(session('success'))
<div class="alert alert-success mb-2">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-2">{{ session('error') }}</div>
@endif
