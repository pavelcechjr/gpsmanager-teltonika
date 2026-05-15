@extends('layouts.app', ['title' => $driver->full_name])
@section('header', 'Řidič: ' . $driver->full_name)

@section('header-actions')
    <x-btn :href="route('ridici.edit', $driver)" variant="secondary" icon="pencil">Upravit</x-btn>
    <x-btn :href="route('ridici.index')" variant="ghost" icon="arrow-left">Zpět</x-btn>
@endsection

@section('content')
    @php
        $parts = preg_split('/\s+/', trim($driver->full_name)) ?: [''];
        $ini = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    @endphp

    {{-- Driver card + stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xl font-semibold text-white mb-3">
                    {{ $ini ?: '?' }}
                </div>
                <div class="font-semibold">{{ $driver->full_name }}</div>
                @if ($driver->email)<div class="text-xs text-zinc-500 mt-0.5">{{ $driver->email }}</div>@endif
                @if ($driver->phone)<div class="text-xs text-zinc-500">{{ $driver->phone }}</div>@endif
                <div class="mt-2">
                    @if ($driver->active)
                        <x-badge variant="green">Aktivní</x-badge>
                    @else
                        <x-badge variant="gray">Neaktivní</x-badge>
                    @endif
                </div>
            </div>
            @if ($driver->note)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <div class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Poznámka</div>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $driver->note }}</p>
                </div>
            @endif
        </div>

        <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
                <div class="text-xs text-zinc-500 uppercase">Jízd v období</div>
                <div class="text-2xl font-semibold mt-1">{{ $stats['count'] }}</div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
                <div class="text-xs text-zinc-500 uppercase">Najeto</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($stats['km'], 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
                <div class="text-xs text-zinc-500 uppercase">Doba</div>
                <div class="text-2xl font-semibold mt-1">
                    {{ intdiv($stats['minutes'], 60) }}<span class="text-sm text-zinc-500 font-normal">h</span>
                    {{ str_pad((string)($stats['minutes'] % 60), 2, '0', STR_PAD_LEFT) }}<span class="text-sm text-zinc-500 font-normal">m</span>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
                <div class="text-xs text-zinc-500 uppercase">Max rychlost</div>
                <div class="text-2xl font-semibold mt-1">{{ $stats['max_kmh'] }} <span class="text-sm text-zinc-500 font-normal">km/h</span></div>
            </div>
        </div>
    </div>

    {{-- Trips table --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <form method="GET" class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-2">
            <div>
                <label class="block text-xs text-zinc-500 uppercase mb-1">Od</label>
                <input type="date" name="from" value="{{ $fromInput }}" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-zinc-500 uppercase mb-1">Do</label>
                <input type="date" name="to" value="{{ $toInput }}" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="self-end"><x-btn type="submit" variant="secondary" icon="filter">Filtrovat</x-btn></div>
        </form>

        @if ($trips->isEmpty())
            <x-empty-state icon="route" title="Žádné jízdy v zadaném období" />
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left  px-4 py-2 font-medium">Datum / čas</th>
                        <th class="text-left  px-4 py-2 font-medium">Vozidlo</th>
                        <th class="text-left  px-4 py-2 font-medium">Trasa</th>
                        <th class="text-right px-4 py-2 font-medium">Doba</th>
                        <th class="text-right px-4 py-2 font-medium">Vzdálenost</th>
                        <th class="text-right px-4 py-2 font-medium">Max km/h</th>
                        <th class="text-right px-4 py-2 font-medium">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($trips as $t)
                        @php
                            $secs = $t->duration_seconds ?: ($t->ended_at && $t->started_at ? $t->ended_at->timestamp - $t->started_at->timestamp : 0);
                            $durStr = $secs ? sprintf('%d:%02d', intdiv($secs, 3600), intdiv($secs % 3600, 60)) : '—';
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 align-top">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div>{{ $t->started_at?->format('d.m.Y') }}</div>
                                <div class="text-xs text-zinc-500">{{ $t->started_at?->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ $t->vehicle?->name ?? '—' }}</div>
                                <div class="text-xs text-zinc-500 font-mono">{{ $t->vehicle?->plate }}</div>
                            </td>
                            <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300 max-w-xs">
                                <div class="truncate">{{ $t->start_address ?? '—' }}</div>
                                <div class="truncate text-xs text-zinc-500">→ {{ $t->end_address ?? '— probíhá —' }}</div>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap font-medium">{{ $durStr }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">{{ $t->distance_meters ? number_format($t->distance_meters / 1000, 1, ',', ' ') . ' km' : '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ $t->max_speed ?? '—' }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('kniha-jizd.show', $t) }}" class="p-1.5 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded" title="Detail s mapou">
                                    <i data-lucide="map" class="w-4 h-4 text-zinc-500"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($trips->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">{{ $trips->links() }}</div>
            @endif
        @endif
    </div>
@endsection
