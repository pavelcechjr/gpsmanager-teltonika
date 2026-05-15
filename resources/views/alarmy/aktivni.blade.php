@extends('layouts.app', ['title' => 'Aktivní alarmy'])
@section('header', 'Aktivní alarmy')

@section('header-actions')
    <x-btn :href="route('alarmy.pravidla')" variant="secondary" icon="settings">Pravidla</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        @if ($events->isEmpty())
            <x-empty-state icon="bell-off" title="Žádné aktivní alarmy" description="Pokud existují aktivní pravidla a Teltonika data tečou, alarmy se zde zobrazí automaticky." />
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left  px-4 py-2 font-medium">Čas</th>
                        <th class="text-left  px-4 py-2 font-medium">Závažnost</th>
                        <th class="text-left  px-4 py-2 font-medium">Pravidlo</th>
                        <th class="text-left  px-4 py-2 font-medium">Vozidlo</th>
                        <th class="text-left  px-4 py-2 font-medium">Popis</th>
                        <th class="text-right px-4 py-2 font-medium">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($events as $e)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div>{{ $e->triggered_at->format('d.m.Y') }}</div>
                                <div class="text-xs text-zinc-500">{{ $e->triggered_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-2">
                                @switch($e->severity)
                                    @case('critical') <x-badge variant="red">Kritické</x-badge> @break
                                    @case('warn')     <x-badge variant="amber">Varování</x-badge> @break
                                    @default          <x-badge variant="blue">Info</x-badge>
                                @endswitch
                            </td>
                            <td class="px-4 py-2">{{ $e->rule?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $e->vehicle?->name ?? '—' }} <span class="text-xs text-zinc-500 font-mono">{{ $e->vehicle?->plate }}</span></td>
                            <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300">{{ $e->summary }}</td>
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="{{ route('alarmy.resolve', $e) }}" class="inline">
                                    @csrf
                                    <x-btn type="submit" variant="ghost" icon="check">Vyřešeno</x-btn>
                                </form>
                            </td>
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
