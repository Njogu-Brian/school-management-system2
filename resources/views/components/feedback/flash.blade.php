@props(['keys' => ['success', 'warning', 'error', 'info']])
@foreach($keys as $key)
    @if(session($key))
        <x-feedback.alert :type="$key === 'error' ? 'danger' : $key" dismissible>{{ session($key) }}</x-feedback.alert>
    @endif
@endforeach
