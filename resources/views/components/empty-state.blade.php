@props(['icon' => 'inbox', 'title', 'description' => null])
<div class="text-center py-16 px-4">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-zinc-100 dark:bg-zinc-800 mb-4">
        <i data-lucide="{{ $icon }}" class="w-7 h-7 text-zinc-500"></i>
    </div>
    <h3 class="text-base font-semibold mb-1">{{ $title }}</h3>
    @if ($description)
        <p class="text-sm text-zinc-500 max-w-md mx-auto">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
