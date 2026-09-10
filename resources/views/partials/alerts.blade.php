<x-feedback.flash :keys="['success', 'error']" />
@if($errors->any())
  <x-feedback.alert type="warning" title="{{ __('Please fix the following:') }}" dismissible>
    <ul class="mb-0 mt-2">
      @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </x-feedback.alert>
@endif
