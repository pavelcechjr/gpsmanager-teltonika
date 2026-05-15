@props(['label' => null, 'name', 'value' => null, 'required' => false, 'help' => null, 'options' => [], 'placeholder' => null, 'empty' => '— nic —'])
@php $val = old($name, $value); @endphp
<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
            {{ $label }}@if($required) <span class="text-red-500">*</span>@endif
        </label>
    @endif
    <select id="{{ $name }}"
            name="{{ $name }}"
            @if($required) required @endif
            {{ $attributes->merge(['class' => 'w-full bg-white dark:bg-zinc-950 border ' . ($errors->has($name) ? 'border-red-500' : 'border-zinc-300 dark:border-zinc-700') . ' rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500']) }}>
        @if (!$required || !$val)
            <option value="">{{ $empty }}</option>
        @endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $val === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
    @enderror
    @if($help)
        <p class="mt-1 text-xs text-zinc-500">{{ $help }}</p>
    @endif
</div>
