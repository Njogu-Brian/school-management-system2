@php
  $homeUrl = Route::has('dashboard')
      ? route('dashboard')
      : (Route::has('home') ? route('home') : url('/'));
@endphp

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item">
      <a href="{{ $homeUrl }}"><i class="bi bi-house"></i></a>
    </li>
    <li class="breadcrumb-item">
      <a href="{{ route('students.index') }}">Students</a>
    </li>

    @if (!empty($trail))
      @foreach ($trail as $label => $url)
        @if ($url)
          <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
        @else
          <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
        @endif
      @endforeach
    @endif
  </ol>
</nav>
