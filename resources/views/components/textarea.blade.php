@props(['label' => null, 'name', 'value' => null, 'rows' => 3])
<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ $label }}</label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              {{ $attributes->merge(['class' => 'w-full bg-white dark:bg-zinc-950 border ' . ($errors->has($name) ? 'border-red-500' : 'border-zinc-300 dark:border-zinc-700') . ' rounded-lg px-3 py-2 text-sm placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500']) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
    @enderror
</div>
