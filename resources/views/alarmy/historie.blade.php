@extends('layouts.app', ['title' => 'Historie alarmů'])
@section('header', 'Historie alarmů')

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        @if ($events->isEmpty())
            <x-empty-state icon="history" title="Žádné vyřešené alarmy" />
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium">Spuštěno</th>
                        <th class="text-left px-4 py-2 font-medium">Vyřešeno</th>
                        <th class="text-left px-4 py-2 font-medium">Pravidlo</th>
                        <th class="text-left px-4 py-2 font-medium">Vozidlo</th>
                        <th class="text-left px-4 py-2 font-medium">Popis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($events as $e)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $e->triggered_at->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $e->resolved_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $e->rule?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $e->vehicle?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $e->summary }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($events->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">{{ $events->links() }}</div>
            @endif
        @endif
    </div>
@endsection
