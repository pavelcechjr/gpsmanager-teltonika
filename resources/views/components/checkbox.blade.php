@props(['label', 'name', 'checked' => false])
<label class="inline-flex items-center gap-2 text-sm cursor-pointer select-none">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
           {{ $attributes->merge(['class' => 'rounded border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0']) }}>
    <span>{{ $label }}</span>
</label>
