@extends('layouts.app', ['title' => 'Skupiny vozidel'])
@section('header', 'Skupiny vozidel')
@section('header-actions')
    <x-btn :href="route('vozidla.skupiny.create')" icon="plus">Nová skupina</x-btn>
@endsection

@section('content')
    @if ($groups->isEmpty())
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl">
            <x-empty-state icon="layers" title="Žádné skupiny" description="Skupiny pomáhají organizovat vozidla podle typu (osobní, dodávky, služebně)." />
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($groups as $g)
                <a href="{{ route('vozidla.skupiny.edit', $g) }}"
                   class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors">
                    <div class="flex items-start gap-3">
                        <span class="w-3 h-3 rounded-full mt-1.5" style="background:{{ $g->color }}"></span>
                        <div class="flex-1">
                            <div class="font-semibold">{{ $g->name }}</div>
                            @if ($g->description)<p class="text-sm text-zinc-500 mt-1 line-clamp-2">{{ $g->description }}</p>@endif
                            <div class="text-xs text-zinc-500 mt-2">{{ $g->vehicles_count }} vozidel</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
