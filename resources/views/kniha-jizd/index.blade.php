@extends('layouts.app', ['title' => 'Kniha jízd'])
@section('header', 'Kniha jízd')

@section('header-actions')
    @php $qs = request()->only(['vehicle_id', 'from', 'to']); @endphp
    <x-btn :href="route('kniha-jizd.export', array_merge($qs, ['format' => 'pdf']))" variant="secondary" icon="file-text">PDF</x-btn>
    <x-btn :href="route('kniha-jizd.export', array_merge($qs, ['format' => 'xlsx']))" variant="secondary" icon="sheet">XLSX</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <form method="GET" class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs text-zinc-500 uppercase tracking-wide mb-1">Vozidlo</label>
                <select name="vehicle_id"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
                    <option value="">Všechna vozidla</option>
                    @foreach ($vehicles as $v)
                        <option value="{{ $v->id }}" @selected($vehicleId == $v->id)>{{ $v->name }} ({{ $v->plate }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-zinc-500 uppercase tracking-wide mb-1">Typ</label>
                <select name="type" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                    <option value="">Všechny</option>
                    <option value="business" @selected($typeFilter === 'business')>Jen služební</option>
                    <option value="private"  @selected($typeFilter === 'private')>Jen soukromé</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-zinc-500 uppercase tracking-wide mb-1">Od</label>
                <input type="date" name="from" value="{{ $fromInput }}"
                       class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs text-zinc-500 uppercase tracking-wide mb-1">Do</label>
                <input type="date" name="to" value="{{ $toInput }}"
                       class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <x-btn type="submit" variant="secondary" icon="filter">Filtrovat</x-btn>
            @if (!$defaultRange)
                <a href="{{ route('kniha-jizd.index') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 pb-2">Reset (24 h)</a>
            @endif
        </form>

        {{-- Totals header (split business/private) --}}
        <div class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><span class="text-zinc-500 uppercase">Služební</span>  <span class="ml-1 font-semibold">{{ number_format($totals['business_km'], 0, ',', ' ') }} km</span> <span class="text-zinc-500">({{ $totals['business_count'] }})</span></div>
            <div><span class="text-zinc-500 uppercase">Soukromé</span>  <span class="ml-1 font-semibold text-amber-600 dark:text-amber-500">{{ number_format($totals['private_km'], 0, ',', ' ') }} km</span> <span class="text-zinc-500">({{ $totals['private_count'] }})</span></div>
            <div><span class="text-zinc-500 uppercase">Celkem</span> <span class="ml-1 font-semibold">{{ number_format($totals['business_km'] + $totals['private_km'], 0, ',', ' ') }} km</span></div>
        </div>

        @if ($defaultRange)
            <div class="px-4 py-2 bg-indigo-500/5 border-b border-indigo-500/20 text-xs text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                Zobrazujeme poslední 24 hodin (default). Pro jiné období použij filtr výše.
            </div>
        @endif

        @if ($trips->isEmpty())
            <x-empty-state icon="route" title="Žádné jízdy v zadaném období"
                           description="Jakmile Teltonika listener (port 5028) přijme polohy a detekuje jízdu, objeví se zde. Pro test můžeš spustit demo seed: php artisan db:seed --class=TripDemoSeeder" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left  px-4 py-3 font-medium">Typ</th>
                            <th class="text-left  px-4 py-3 font-medium">Vozidlo</th>
                            <th class="text-left  px-4 py-3 font-medium">Zahájení</th>
                            <th class="text-left  px-4 py-3 font-medium">Místo zahájení</th>
                            <th class="text-left  px-4 py-3 font-medium">Konec</th>
                            <th class="text-left  px-4 py-3 font-medium">Místo konce</th>
                            <th class="text-left  px-4 py-3 font-medium">Doba</th>
                            <th class="text-right px-4 py-3 font-medium">km</th>
                            <th class="text-right px-4 py-3 font-medium">Tachometr</th>
                            <th class="text-right px-4 py-3 font-medium">l/100km</th>
                            <th class="text-center px-4 py-3 font-medium">⚠</th>
                            <th class="text-left  px-4 py-3 font-medium">Řidič</th>
                            <th class="text-left  px-4 py-3 font-medium">Poznámka</th>
                            <th class="text-right px-4 py-3 font-medium">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @php
                            // Merge calibrations into the trip stream chronologically (by date desc; falls within page if same range).
                            // We render trips from paginator + interleave calibrations that fall in same date span.
                            $tripsArr = $trips->getCollection()->all();
                            $calArr   = $calibrations->all();
                            // Combine with a key 'ts' for sort
                            $stream = [];
                            foreach ($tripsArr as $t) { $stream[] = ['ts' => $t->started_at, 'type' => 'trip',  'item' => $t]; }
                            foreach ($calArr as $c)   { $stream[] = ['ts' => $c->applied_at, 'type' => 'calib', 'item' => $c]; }
                            usort($stream, fn ($a, $b) => $b['ts'] <=> $a['ts']);
                        @endphp
                        @foreach ($stream as $row)
                            @if ($row['type'] === 'calib')
                                @php $c = $row['item']; @endphp
                                <tr class="bg-amber-50 dark:bg-amber-500/5 hover:bg-amber-100 dark:hover:bg-amber-500/10">
                                    <td class="px-4 py-3">
                                        <x-badge variant="amber" class="inline-flex items-center gap-1">
                                            <i data-lucide="sliders-horizontal" class="w-3 h-3"></i>
                                            <span>Kalibrace</span>
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $c->vehicle?->name }}</div>
                                        <div class="text-xs text-zinc-500 font-mono">{{ $c->vehicle?->plate }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div>{{ $c->applied_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-zinc-500">{{ $c->applied_at->format('H:i') }}</div>
                                    </td>
                                    <td colspan="3" class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        <span class="font-medium">Kalibrace tachometru</span>
                                        @if ($c->note) — {{ $c->note }} @endif
                                    </td>
                                    <td class="px-4 py-3 text-zinc-400 text-xs">—</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold {{ $c->delta_km >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $c->delta_km > 0 ? '+' : '' }}{{ number_format($c->delta_km, 0, ',', ' ') }} km
                                    </td>
                                    <td class="px-4 py-3 text-zinc-400 text-xs">—</td>
                                    <td class="px-4 py-3 text-zinc-400 text-xs">—</td>
                                    <td class="px-4 py-3 text-zinc-400 text-xs">—</td>
                                    <td class="px-4 py-3 text-zinc-400 text-xs">{{ $c->note }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('vozidla.kalibrace.edit', ['vozidla' => $c->vehicle_id, 'calibration' => $c->id]) }}" class="p-2 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Upravit kalibraci">
                                            <i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i>
                                        </a>
                                    </td>
                                </tr>
                                @continue
                            @endif
                            @php $trip = $row['item']; @endphp
                            @php
                                $durationSecs = $trip->duration_seconds
                                    ?: ($trip->ended_at && $trip->started_at ? $trip->ended_at->diffInSeconds($trip->started_at) : null);
                                $durStr = $durationSecs
                                    ? sprintf('%d h %02d min', intdiv($durationSecs, 3600), intdiv($durationSecs % 3600, 60))
                                    : '—';
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 align-top">
                                <td class="px-4 py-3">
                                    @if ($trip->is_private)
                                        <x-badge variant="amber" class="inline-flex items-center gap-1">
                                            <i data-lucide="house" class="w-3 h-3"></i>
                                            <span>Soukromá</span>
                                        </x-badge>
                                    @else
                                        <x-badge variant="blue" class="inline-flex items-center gap-1">
                                            <i data-lucide="briefcase" class="w-3 h-3"></i>
                                            <span>Služební</span>
                                        </x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $trip->vehicle?->name ?? '—' }}</div>
                                    <div class="text-xs text-zinc-500 font-mono">{{ $trip->vehicle?->plate }}</div>
                                    @if ($trip->vehicle?->is_eco)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 mt-1 rounded-md text-[10px] font-medium bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                            <i data-lucide="leaf" class="w-3 h-3"></i>
                                            <span>{{ $trip->vehicle->fuel_type_short }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($trip->started_at)
                                        <div>{{ $trip->started_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-zinc-500">{{ $trip->started_at->format('H:i:s') }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 max-w-xs">
                                    {{ $trip->start_address ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($trip->ended_at)
                                        <div>{{ $trip->ended_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-zinc-500">{{ $trip->ended_at->format('H:i:s') }}</div>
                                    @else
                                        <x-badge variant="amber">Probíhá</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 max-w-xs">
                                    {{ $trip->end_address ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium">{{ $durStr }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $trip->distance_meters ? number_format($trip->distance_meters / 1000, 1, ',', ' ') : '—' }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $trip->odometer_end_km ? number_format($trip->odometer_end_km, 0, ',', ' ') : '—' }}</td>
                                <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">{{ $trip->fuel_consumption_l_100km ? number_format($trip->fuel_consumption_l_100km, 1, ',', ' ') : '—' }}</td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @php $warns = $trip->warnings; @endphp
                                    @if (!empty($warns))
                                        <div class="inline-flex flex-wrap items-center justify-center gap-1" title="{{ collect($warns)->map(fn($w) => $w['label'])->implode(' · ') }}">
                                            @foreach ($warns as $w)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $w['severity'] === 'red' ? 'bg-red-500/15 text-red-500' : 'bg-amber-500/15 text-amber-500' }}">
                                                    @if ($w['type'] === 'dtc') <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                                    @elseif ($w['type'] === 'overheat') <i data-lucide="thermometer" class="w-3 h-3"></i>
                                                    @elseif (in_array($w['type'], ['low_volt','overcharge'])) <i data-lucide="battery-warning" class="w-3 h-3"></i>
                                                    @elseif (in_array($w['type'], ['hard_accel','hard_brake'])) <i data-lucide="zap" class="w-3 h-3"></i>
                                                    @elseif ($w['type'] === 'high_rpm') <i data-lucide="gauge" class="w-3 h-3"></i>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-zinc-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($trip->driver)
                                        {{ $trip->driver->full_name }}
                                    @else
                                        <span class="text-zinc-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-w-xs">
                                    @if ($trip->note)
                                        <span class="line-clamp-2">{{ $trip->note }}</span>
                                    @else
                                        <span class="text-zinc-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('kniha-jizd.toggle-type', $trip) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Přepnout soukromá / služební">
                                            <i data-lucide="{{ $trip->is_private ? 'home' : 'briefcase' }}" class="w-4 h-4 {{ $trip->is_private ? 'text-amber-500' : 'text-blue-500' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('kniha-jizd.show', $trip) }}" class="p-2 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Detail jízdy + mapa">
                                        <i data-lucide="map" class="w-4 h-4 text-zinc-500"></i>
                                    </a>
                                    <a href="{{ route('kniha-jizd.edit', $trip) }}" class="p-2 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Upravit řidiče / poznámku">
                                        <i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($trips->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 text-sm">
                    {{ $trips->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
