@props(['name', 'label' => null, 'value' => null, 'help' => null, 'required' => false])
@php $hasError = $errors->has($name); $id = $attributes->get('id', $name); @endphp
<x-form.field :label="$label" :name="$name" :help="$help" :required="$required" :id="$id">
    <textarea id="{{ $id }}" name="{{ $name }}" {{ $attributes->except(['id'])->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
        @required($required) @if($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>{{ old($name, $value) }}</textarea>
</x-form.field>
