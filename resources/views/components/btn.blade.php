@props(['variant' => 'primary', 'icon' => null, 'href' => null, 'type' => 'submit'])
@php
    $variants = [
        'primary'   => 'bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white shadow-sm',
        'secondary' => 'bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200',
        'danger'    => 'bg-red-600 hover:bg-red-500 active:bg-red-700 text-white shadow-sm',
        'ghost'     => 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200',
    ];
    $class = 'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ' . ($variants[$variant] ?? $variants['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<i data-lucide="{{ $icon }}" class="w-4 h-4"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<i data-lucide="{{ $icon }}" class="w-4 h-4"></i>@endif
        {{ $slot }}
    </button>
@endif
