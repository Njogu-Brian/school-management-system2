@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'help' => null, 'required' => false])
@php $hasError = $errors->has($name); $id = $attributes->get('id', $name); @endphp
<x-form.field :label="$label" :name="$name" :help="$help" :required="$required" :id="$id">
    <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->except(['id'])->merge(['class' => 'form-select' . ($hasError ? ' is-invalid' : '')]) }}
        @required($required) @if($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
        @if($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
</x-form.field>
