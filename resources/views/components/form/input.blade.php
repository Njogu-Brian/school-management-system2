@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php $hasError = $errors->has($name); $id = $attributes->get('id', $name); @endphp
<x-form.field :label="$label" :name="$name" :help="$help" :required="$required" :id="$id">
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        {{ $attributes->except(['id'])->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
        @required($required) @if($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
</x-form.field>
