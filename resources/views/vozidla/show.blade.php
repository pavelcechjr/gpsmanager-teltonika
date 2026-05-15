@extends('layouts.app', ['title' => $vehicle->name])
@section('header', $vehicle->name . ' — ' . $vehicle->plate)

@section('header-actions')
    <x-btn :href="route('vozidla.edit', $vehicle)" variant="secondary" icon="pencil">Upravit</x-btn>
    <x-btn :href="route('vozidla.index')" variant="ghost" icon="arrow-left">Zpět</x-btn>
@endsection

@section('content')
    {{-- KPI row — najeté km --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="flex items-center gap-2">
                <div class="text-xs text-zinc-500 uppercase">Tachometr</div>
                @if ($vehicle->odometer_source === 'obd')
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-500/15 text-emerald-500">OBD</span>
                @elseif ($vehicle->odometer_source === 'manual')
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-500/15 text-amber-500">Manuální</span>
                @endif
            </div>
            @if ($vehicle->current_odometer_km !== null)
                <div class="text-2xl font-semibold mt-1">{{ number_format($vehicle->current_odometer_km, 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
                <div class="text-xs text-zinc-500 mt-1">
                    @if ($vehicle->odometer_source === 'obd')
                        z ECU auta (live)
                    @else
                        <span title="Zadáno {{ number_format($totals['odometer_baseline'] ?? 0, 0, ',', ' ') }} km{{ ($totals['odometer_updated_at'] ?? null) ? ' dne ' . $totals['odometer_updated_at']->format('d.m.Y') : '' }}, +{{ $totals['tracked_since_base'] ?? 0 }} km z jízd">baseline + jízdy</span>
                    @endif
                </div>
            @else
                <div class="text-2xl font-semibold mt-1 text-zinc-400">—</div>
                <div class="text-xs text-zinc-500 mt-1"><a href="{{ route('vozidla.edit', $vehicle) }}" class="text-indigo-500 hover:underline">zadej v Upravit</a></div>
            @endif
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Najeto dnes</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['km_today'], 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Tento měsíc</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['km_month'], 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Posledních 12 měs.</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['km_year'], 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Trackovaných km</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['km_total'], 0, ',', ' ') }}</div>
            <div class="text-xs text-zinc-500">{{ $totals['trips_total'] }} jízd</div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Max rychlost</div>
            <div class="text-2xl font-semibold mt-1">{{ $totals['max_speed'] }} <span class="text-sm text-zinc-500 font-normal">km/h</span></div>
        </div>
    </div>

    {{-- Vehicle + driver + device info --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <i data-lucide="truck" class="w-6 h-6 text-blue-500"></i>
                </div>
                <div>
                    <div class="font-semibold">{{ $vehicle->name }}</div>
                    <div class="text-xs text-zinc-500 font-mono">{{ $vehicle->plate }}</div>
                </div>
            </div>
            <dl class="text-sm space-y-1.5">
                @if ($vehicle->color)
                    <div class="flex justify-between"><dt class="text-zinc-500">Barva</dt><dd>{{ $vehicle->color }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-zinc-500">Default řidič</dt><dd>{{ $vehicle->defaultDriver?->full_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Status</dt><dd>@if($vehicle->active) <x-badge variant="green">Aktivní</x-badge> @else <x-badge variant="gray">Neaktivní</x-badge> @endif</dd></div>
                @if ($vehicle->note)
                    <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-600 dark:text-zinc-400">{{ $vehicle->note }}</div>
                @endif
            </dl>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-lg bg-purple-500/10 flex items-center justify-center">
                    <i data-lucide="satellite" class="w-6 h-6 text-purple-500"></i>
                </div>
                <div>
                    <div class="font-semibold">Teltonika jednotka</div>
                    <div class="text-xs text-zinc-500 font-mono">{{ $vehicle->device?->imei ?? '— nepřiřazena —' }}</div>
                </div>
            </div>
            @if ($vehicle->device)
                <dl class="text-sm space-y-1.5">
                    <div class="flex justify-between"><dt class="text-zinc-500">Model</dt><dd>{{ $vehicle->device->model ?? '—' }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">Naposledy slyšeno</dt>
                        <dd>
                            @if ($vehicle->device->last_seen_at)
                                <span title="{{ $vehicle->device->last_seen_at->format('d.m.Y H:i:s') }}">{{ $vehicle->device->last_seen_at->diffForHumans(['short' => true]) }}</span>
                            @else <span class="text-zinc-400 text-xs">nepřipojeno</span>
                            @endif
                        </dd>
                    </div>
                    @if ($latest?->vin)
                        <div class="flex justify-between"><dt class="text-zinc-500">VIN</dt><dd class="font-mono text-xs">{{ $latest->vin }}</dd></div>
                    @endif
                </dl>
            @endif
        </div>

        {{-- Current telemetry --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <i data-lucide="activity" class="w-6 h-6 text-emerald-500"></i>
                </div>
                <div>
                    <div class="font-semibold">Aktuální stav (OBD)</div>
                    <div class="text-xs text-zinc-500">{{ $latest?->recorded_at?->diffForHumans(['short' => true]) ?? 'žádná data' }}</div>
                </div>
            </div>
            @if ($latest)
                <dl class="text-sm space-y-1.5">
                    @if ($latest->fuel_level !== null)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Palivo</dt>
                            <dd>
                                {{ $latest->fuel_level }} %
                                @if ($vehicle->current_fuel_liters !== null)
                                    <span class="text-zinc-500">≈ {{ number_format($vehicle->current_fuel_liters, 1, ',', ' ') }} L</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($latest->coolant_temp !== null)
                        <div class="flex justify-between"><dt class="text-zinc-500">Teplota chladiče</dt><dd>{{ $latest->coolant_temp }} °C</dd></div>
                    @endif
                    @if ($latest->external_voltage !== null)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">12V napájení</dt>
                            <dd>
                                <span class="{{ $latest->external_voltage < 12.0 ? 'text-red-500 font-medium' : '' }}">{{ number_format($latest->external_voltage, 2, ',', '') }} V</span>
                            </dd>
                        </div>
                    @endif
                    @if ($latest->engine_rpm !== null)
                        <div class="flex justify-between"><dt class="text-zinc-500">Otáčky</dt><dd>{{ $latest->engine_rpm }} rpm</dd></div>
                    @endif
                    @if ($latest->dtc_count !== null)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Chybové kódy</dt>
                            <dd>
                                @if ($latest->dtc_count > 0)
                                    <x-badge variant="red">{{ $latest->dtc_count }} DTC</x-badge>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400">0</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($latest->ignition !== null)
                        <div class="flex justify-between"><dt class="text-zinc-500">Zapalování</dt><dd>{{ $latest->ignition ? 'ON' : 'OFF' }}</dd></div>
                    @endif
                </dl>
            @else
                <div class="text-sm text-zinc-400">Žádná telemetrie zatím.</div>
            @endif
        </div>
    </div>

    {{-- km trend chart --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 mb-4">
        <div class="flex items-center justify-between mb-1">
            <div>
                <h3 class="text-sm font-semibold tracking-tight">Najeté km / den</h3>
                <p class="text-xs text-zinc-500">Posledních 30 dnů (uzavřené jízdy)</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-semibold">{{ number_format(array_sum($trendKm), 0, ',', ' ') }} km</div>
                <div class="text-xs text-zinc-500">{{ array_sum($trendTrips) }} jízd</div>
            </div>
        </div>
        <div id="kmChart" style="height:240px"></div>
    </div>

    {{-- 12V voltage trend --}}
    @if (count($voltageTrend) > 1)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 mb-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-semibold tracking-tight">12V napájení — posledních 7 dnů</h3>
                <p class="text-xs text-zinc-500">{{ count($voltageTrend) }} měření</p>
            </div>
            <p class="text-xs text-zinc-500 mb-2">Hodnota &lt; 12,0 V naznačuje slabou baterii (parking drain). Hodnota 13,5 – 14,5 V = motor běží + alternátor nabíjí.</p>
            <div id="voltLongChart" style="height:240px"></div>
        </div>
    @endif

    {{-- Calibrations table --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden mb-4">
        <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h3 class="text-sm font-semibold">Kalibrace tachometru</h3>
                <p class="text-xs text-zinc-500">Ruční korekce stavu tachometru.</p>
            </div>
            <x-btn :href="route('vozidla.kalibrace.create', $vehicle)" variant="secondary" icon="plus">Přidat kalibraci</x-btn>
        </div>
        @php $cals = $vehicle->calibrations()->orderByDesc('applied_at')->limit(20)->get(); @endphp
        @if ($cals->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-zinc-500">Zatím žádná kalibrace.</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left  px-5 py-2 font-medium">Datum</th>
                        <th class="text-right px-5 py-2 font-medium">Korekce</th>
                        <th class="text-left  px-5 py-2 font-medium">Poznámka</th>
                        <th class="text-right px-5 py-2 font-medium">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($cals as $c)
                        <tr>
                            <td class="px-5 py-2 whitespace-nowrap">{{ $c->applied_at->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-2 text-right font-semibold {{ $c->delta_km >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $c->delta_km > 0 ? '+' : '' }}{{ number_format($c->delta_km, 0, ',', ' ') }} km
                            </td>
                            <td class="px-5 py-2 text-zinc-600 dark:text-zinc-400">{{ $c->note ?? '—' }}</td>
                            <td class="px-5 py-2 text-right">
                                <a href="{{ route('vozidla.kalibrace.edit', ['vozidla' => $vehicle, 'calibration' => $c]) }}" class="p-1.5 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded"><i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Recent trips table --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-200 dark:border-zinc-800">
            <h3 class="text-sm font-semibold">Poslední jízdy</h3>
            <a href="{{ route('kniha-jizd.index', ['vehicle_id' => $vehicle->id]) }}" class="text-xs text-indigo-500 hover:text-indigo-600">Všechny jízdy →</a>
        </div>
        @if ($recentTrips->isEmpty())
            <x-empty-state icon="route" title="Žádné jízdy" />
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left  px-4 py-2 font-medium">Datum / čas</th>
                        <th class="text-left  px-4 py-2 font-medium">Trasa</th>
                        <th class="text-right px-4 py-2 font-medium">Doba</th>
                        <th class="text-right px-4 py-2 font-medium">km</th>
                        <th class="text-right px-4 py-2 font-medium">Max km/h</th>
                        <th class="text-left  px-4 py-2 font-medium">Řidič</th>
                        <th class="text-right px-4 py-2 font-medium">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($recentTrips as $t)
                        @php
                            $secs = $t->duration_seconds ?: ($t->ended_at && $t->started_at ? $t->ended_at->timestamp - $t->started_at->timestamp : 0);
                            $durStr = $secs ? sprintf('%d:%02d', intdiv($secs, 3600), intdiv($secs % 3600, 60)) : '—';
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div>{{ $t->started_at?->format('d.m.Y') }}</div>
                                <div class="text-xs text-zinc-500">{{ $t->started_at?->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-2 max-w-xs">
                                <div class="truncate">{{ $t->start_address ?? '—' }}</div>
                                <div class="truncate text-xs text-zinc-500">→ {{ $t->end_address ?? '— probíhá —' }}</div>
                            </td>
                            <td class="px-4 py-2 text-right">{{ $durStr }}</td>
                            <td class="px-4 py-2 text-right">{{ $t->distance_meters ? number_format($t->distance_meters / 1000, 1, ',', ' ') : '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ $t->max_speed ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $t->driver?->full_name ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('kniha-jizd.show', $t) }}" class="p-1.5 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded"><i data-lucide="map" class="w-4 h-4 text-zinc-500"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @php
        $trendKmJson    = json_encode($trendKm);
        $trendTripsJson = json_encode($trendTrips);
        $trendLblJson   = json_encode($trendLabels);
        $voltLongJson   = json_encode($voltageTrend);
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@4"></script>
    <script>
    (function () {
        const isDark = document.documentElement.classList.contains('dark');
        const axis = isDark ? '#a1a1aa' : '#52525b';
        const grid = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

        const km    = {!! $trendKmJson !!};
        const tr    = {!! $trendTripsJson !!};
        const lbls  = {!! $trendLblJson !!};

        new ApexCharts(document.querySelector('#kmChart'), {
            chart: { type: 'area', height: 240, toolbar: { show: false }, fontFamily: 'Inter,sans-serif', background: 'transparent' },
            theme: { mode: isDark ? 'dark' : 'light' },
            series: [
                { name: 'km',   type: 'area', data: km },
                { name: 'jízd', type: 'line', data: tr },
            ],
            xaxis: { categories: lbls, labels: { style: { colors: axis, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: [
                { labels: { style: { colors: axis, fontSize: '11px' }, formatter: v => Math.round(v) + ' km' } },
                { opposite: true, labels: { style: { colors: axis, fontSize: '11px' }, formatter: v => Math.round(v) } },
            ],
            colors: ['#6366f1', '#f59e0b'],
            fill: { type: ['gradient', 'solid'], gradient: { shadeIntensity: 0.6, opacityFrom: 0.45, opacityTo: 0 } },
            stroke: { curve: 'smooth', width: [2, 2] },
            grid: { borderColor: grid, strokeDashArray: 3 },
            tooltip: { theme: isDark ? 'dark' : 'light', shared: true, intersect: false },
            legend: { show: false },
            markers: { size: 0 },
        }).render();

        const volts = {!! $voltLongJson !!};
        if (volts.length > 1 && document.querySelector('#voltLongChart')) {
            new ApexCharts(document.querySelector('#voltLongChart'), {
                chart: { type: 'line', height: 240, toolbar: { show: false }, fontFamily: 'Inter,sans-serif', background: 'transparent' },
                theme: { mode: isDark ? 'dark' : 'light' },
                series: [{ name: 'V', data: volts.map(p => p.v) }],
                xaxis: { categories: volts.map(p => p.t), labels: { style: { colors: axis, fontSize: '10px' }, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { style: { colors: axis, fontSize: '10px' }, formatter: v => v.toFixed(2) + ' V' }, min: function (m) { return Math.min(m, 11); }, max: function (m) { return Math.max(m, 15); } },
                colors: ['#3b82f6'],
                stroke: { curve: 'smooth', width: 2 },
                grid: { borderColor: grid, strokeDashArray: 3 },
                tooltip: { theme: isDark ? 'dark' : 'light' },
                annotations: { yaxis: [
                    { y: 12.0, borderColor: '#ef4444', label: { text: 'Slabá baterie', style: { background: '#ef4444', color: '#fff', fontSize: '10px' } } },
                    { y: 13.5, borderColor: '#10b981', borderWidth: 1, label: { text: 'Nabíjení', style: { background: '#10b981', color: '#fff', fontSize: '10px' } } },
                ]},
            }).render();
        }
    })();
    </script>
@endsection
