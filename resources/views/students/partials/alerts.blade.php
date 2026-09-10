<x-feedback.flash :keys="['success', 'warning', 'error']" />
@if ($errors->any())
  <x-feedback.alert type="danger" title="Please fix the errors below">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </x-feedback.alert>
@endif
