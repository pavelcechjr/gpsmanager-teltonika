@props(['variant' => 'gray'])
@php
    $variants = [
        'gray'    => 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300',
        'green'   => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        'red'     => 'bg-red-500/10 text-red-600 dark:text-red-400',
        'amber'   => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        'indigo'  => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
        'blue'    => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    ];
    $class = $variants[$variant] ?? $variants['gray'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' . $class]) }}>
    {{ $slot }}
</span>
