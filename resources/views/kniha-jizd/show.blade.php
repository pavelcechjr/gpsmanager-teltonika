@extends('layouts.app', ['title' => 'Detail jízdy'])
@section('header', 'Detail jízdy')

@section('header-actions')
    <x-btn :href="route('kniha-jizd.edit', $trip)" variant="secondary" icon="pencil">Upravit</x-btn>
    <x-btn :href="route('kniha-jizd.index')" variant="ghost" icon="arrow-left">Zpět</x-btn>
@endsection

@section('content')
    @php
        $durationSecs = $trip->duration_seconds
            ?: ($trip->ended_at && $trip->started_at ? $trip->ended_at->timestamp - $trip->started_at->timestamp : null);
        $durStr = $durationSecs
            ? sprintf('%d h %02d min', intdiv($durationSecs, 3600), intdiv($durationSecs % 3600, 60))
            : '—';
        $km = $trip->distance_meters ? number_format($trip->distance_meters / 1000, 2, ',', ' ') : '—';
    @endphp

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Vozidlo</div>
            <div class="text-base font-semibold mt-0.5 truncate">{{ $trip->vehicle?->name ?? '—' }}</div>
            <div class="flex items-center gap-2 flex-wrap mt-1">
                <div class="text-xs text-zinc-500 font-mono">{{ $trip->vehicle?->plate }}</div>
                @if ($trip->vehicle?->is_eco)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                        <i data-lucide="leaf" class="w-3 h-3"></i>
                        <span>{{ $trip->vehicle->fuel_type_short }}</span>
                    </span>
                @endif
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Doba</div>
            <div class="text-base font-semibold mt-0.5">{{ $durStr }}</div>
            <div class="text-xs text-zinc-500">{{ $stats['positions'] }} pozic</div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Vzdálenost</div>
            <div class="text-base font-semibold mt-0.5">{{ $km }} <span class="text-xs text-zinc-500 font-normal">km</span></div>
            <div class="text-xs text-zinc-500">max {{ (int)($trip->max_speed ?? 0) }} km/h · prům {{ $stats['avg_speed'] }} km/h</div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Řidič</div>
            <div class="text-base font-semibold mt-0.5 truncate">{{ $trip->driver?->full_name ?? '—' }}</div>
            <div class="text-xs text-zinc-500 truncate">{{ $trip->device?->imei }}</div>
        </div>
    </div>

    {{-- Map + sidebar --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
            @if (count($polyline) >= 2)
                <div id="trip-map" class="w-full" style="height:400px;background:#0f172a"></div>
            @else
                <div class="p-12 text-center text-sm text-zinc-500">
                    <i data-lucide="map" class="w-10 h-10 mx-auto mb-2 text-zinc-400"></i>
                    Pro vykreslení trasy jsou potřeba alespoň 2 pozice — tato jízda má {{ count($polyline) }}.
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold tracking-tight mb-3">Trasa</h3>
            <div class="space-y-3 text-sm">
                <div class="flex gap-3">
                    <div class="shrink-0 w-2 h-2 rounded-full bg-emerald-500 mt-1.5"></div>
                    <div class="flex-1">
                        <div class="text-xs text-zinc-500">Start</div>
                        <div class="font-medium">{{ $trip->started_at?->format('d.m.Y H:i:s') ?? '—' }}</div>
                        <div class="text-zinc-600 dark:text-zinc-400">{{ $trip->start_address ?? '—' }}</div>
                    </div>
                </div>
                <div class="border-l-2 border-dashed border-zinc-300 dark:border-zinc-700 ml-1 pl-5 -my-1 h-4"></div>
                <div class="flex gap-3">
                    <div class="shrink-0 w-2 h-2 rounded-full bg-red-500 mt-1.5"></div>
                    <div class="flex-1">
                        <div class="text-xs text-zinc-500">Konec</div>
                        @if ($trip->ended_at)
                            <div class="font-medium">{{ $trip->ended_at->format('d.m.Y H:i:s') }}</div>
                            <div class="text-zinc-600 dark:text-zinc-400">{{ $trip->end_address ?? '—' }}</div>
                        @else
                            <div class="font-medium text-amber-600 dark:text-amber-400">— probíhá —</div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($trip->note)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <div class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Poznámka</div>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $trip->note }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Telemetrie panel (OBD + system) ───────────────────────────── --}}
    @if (!empty($telemetry))
        @php
            $tiles = [
                ['Max otáčky',       $telemetry['rpm_max'],         'rpm',  'gauge',       'text-orange-500'],
                ['Průměrné otáčky',  $telemetry['rpm_avg'],         'rpm',  'activity',    'text-amber-500'],
                ['Max OBD rychlost', $telemetry['speed_obd_max'],   'km/h', 'rocket',      'text-blue-500'],
                ['Max GPS rychlost', $telemetry['speed_gps_max'],   'km/h', 'satellite',   'text-cyan-500'],
                ['Max teplota chl.', $telemetry['coolant_max'],     '°C',   'thermometer', 'text-red-500'],
                ['Max plyn',         $telemetry['throttle_max'],    '%',    'pedal',       'text-emerald-500'],
                ['Max zátěž motoru', $telemetry['engine_load_max'], '%',    'cpu',         'text-purple-500'],
                ['Chyb. kódy',       $telemetry['dtc_max'],         '',     'alert-triangle','text-red-500'],
            ];
        @endphp
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl mb-4">
            <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold tracking-tight">Telemetrie z OBD</h3>
                    <p class="text-xs text-zinc-500">{{ $telemetry['records'] }} záznamů · pohyb v {{ $telemetry['movement_records'] }} · zapalování v {{ $telemetry['ignition_records'] }}</p>
                </div>
                @if ($telemetry['vin'])
                    <div class="text-xs text-zinc-500 font-mono"><span class="text-zinc-400">VIN:</span> {{ $telemetry['vin'] }}</div>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 p-4">
                @foreach ($tiles as [$label, $val, $unit, $icon, $color])
                    <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                        <div class="flex items-start justify-between">
                            <div class="min-w-0">
                                <div class="text-xs text-zinc-500 truncate">{{ $label }}</div>
                                <div class="text-base font-semibold mt-0.5">
                                    @if ($val !== null)
                                        {{ $val }}<span class="text-xs text-zinc-500 font-normal ml-0.5">{{ $unit }}</span>
                                    @else
                                        <span class="text-zinc-400 text-sm">—</span>
                                    @endif
                                </div>
                            </div>
                            <i data-lucide="{{ $icon }}" class="w-4 h-4 shrink-0 {{ $color }}"></i>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Fuel + battery + odometer row --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 px-4 pb-4">
                <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-zinc-500 uppercase tracking-wide">Spotřeba</div>
                        <i data-lucide="fuel" class="w-4 h-4 text-emerald-500"></i>
                    </div>
                    @if ($trip->fuel_consumption_l_100km !== null)
                        <div class="text-2xl font-semibold">{{ number_format($trip->fuel_consumption_l_100km, 1, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">l/100km</span></div>
                        <div class="text-xs text-zinc-500 mt-1">
                            Spotřebováno {{ number_format($trip->fuel_consumed_l, 2, ',', ' ') }} l
                            @if ($trip->fuel_start_pct !== null) · {{ $trip->fuel_start_pct }} % → {{ $trip->fuel_end_pct }} %  @endif
                        </div>
                    @elseif ($telemetry['fuel_start'] !== null)
                        <div class="text-2xl font-semibold">{{ $telemetry['fuel_end'] }} <span class="text-sm text-zinc-500 font-normal">%</span></div>
                        <div class="text-xs text-zinc-500 mt-1">
                            Před jízdou: {{ $telemetry['fuel_start'] }} %
                            @if ($trip->vehicle?->fuel_tank_l === null)
                                <br><a href="{{ route('vozidla.edit', $trip->vehicle) }}" class="text-indigo-500 hover:underline">Zadej objem nádrže</a> pro výpočet l/100km
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-zinc-400 mt-2">— OBD neposílá fuel level —</div>
                    @endif
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-zinc-500 uppercase tracking-wide">Napájení (12V)</div>
                        <i data-lucide="battery-charging" class="w-4 h-4 text-blue-500"></i>
                    </div>
                    @if ($telemetry['ext_voltage_min'] !== null)
                        <div class="text-2xl font-semibold">
                            {{ number_format($telemetry['ext_voltage_min'], 2, ',', ' ') }}<span class="text-sm text-zinc-500"> – </span>{{ number_format($telemetry['ext_voltage_max'], 2, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">V</span>
                        </div>
                        <div class="text-xs text-zinc-500 mt-1">
                            min – max během jízdy
                            @if ($telemetry['ext_voltage_min'] < 12.0)
                                · <span class="text-red-500 font-medium">slabá baterie!</span>
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-zinc-400 mt-2">—</div>
                    @endif
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs text-zinc-500 uppercase tracking-wide">OBD odometr</div>
                        <i data-lucide="gauge" class="w-4 h-4 text-zinc-500"></i>
                    </div>
                    @if ($telemetry['odo_end'])
                        <div class="text-2xl font-semibold">{{ number_format($telemetry['odo_end'], 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
                        @if ($telemetry['odo_start'] && $telemetry['odo_end'] > $telemetry['odo_start'])
                            <div class="text-xs text-zinc-500 mt-1">+ {{ $telemetry['odo_end'] - $telemetry['odo_start'] }} km během jízdy</div>
                        @endif
                    @else
                        <div class="text-sm text-zinc-400 mt-2">— OBD odometr neposílá —</div>
                    @endif
                </div>
            </div>

            {{-- Telemetry charts (voltage, RPM, speed) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 px-4 pb-4">
                <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                    <div class="text-xs text-zinc-500 font-semibold mb-1">12V napájení v čase</div>
                    <div id="voltChart" style="height:160px"></div>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                    <div class="text-xs text-zinc-500 font-semibold mb-1">RPM motoru v čase</div>
                    <div id="rpmChart" style="height:160px"></div>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                    <div class="text-xs text-zinc-500 font-semibold mb-1">Rychlost: GPS vs OBD</div>
                    <div id="speedChart" style="height:160px"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- ─── HV baterie panel (jen pro hybrid / PHEV / EV) ───────────────── --}}
    @if ($trip->vehicle?->is_eco)
        @php
            // Najdi první/poslední position s HV SOC (AVL ID 850)
            $socStart = $positions->pluck('io_data')->map(fn($io) => $io['850'] ?? null)->filter()->first();
            $socEnd   = $positions->pluck('io_data')->map(fn($io) => $io['850'] ?? null)->filter()->last();
            $hvVoltMax = $positions->pluck('io_data')->map(fn($io) => isset($io['851']) ? (float)$io['851'] * 0.1 : null)->filter()->max();
            $hvTempMax = $positions->pluck('io_data')->map(fn($io) => $io['853'] ?? null)->filter()->max();
            $evMode    = $positions->pluck('io_data')->map(fn($io) => $io['854'] ?? null)->filter();
            $evModePct = $evMode->isNotEmpty() ? round($evMode->where(fn($v) => $v == 1)->count() * 100 / $evMode->count()) : null;
            $hasHvData = $socStart !== null || $hvVoltMax !== null || $evMode->isNotEmpty();
        @endphp
        <div class="bg-white dark:bg-zinc-900 border border-emerald-500/30 rounded-xl mb-4">
            <div class="px-5 py-3 border-b border-emerald-500/20 flex items-center justify-between bg-emerald-500/5">
                <div class="flex items-center gap-2">
                    <i data-lucide="leaf" class="w-4 h-4 text-emerald-500"></i>
                    <h3 class="text-sm font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">
                        Trakční baterie / Elektro telemetrie
                    </h3>
                    <span class="text-xs text-zinc-500">({{ $trip->vehicle->fuel_type_label }})</span>
                </div>
            </div>

            @if ($hasHvData)
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 p-4">
                    <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                        <div class="text-xs text-zinc-500">SOC start</div>
                        <div class="text-base font-semibold mt-0.5">{{ $socStart ?? '—' }}<span class="text-xs text-zinc-500 font-normal ml-0.5">%</span></div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                        <div class="text-xs text-zinc-500">SOC konec</div>
                        <div class="text-base font-semibold mt-0.5">{{ $socEnd ?? '—' }}<span class="text-xs text-zinc-500 font-normal ml-0.5">%</span></div>
                    </div>
                    @if ($trip->ev_consumption_kwh_100km !== null)
                        <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                            <div class="text-xs text-zinc-500">El. spotřeba</div>
                            <div class="text-base font-semibold mt-0.5">{{ $trip->ev_consumption_kwh_100km }}<span class="text-xs text-zinc-500 font-normal ml-0.5">kWh/100km</span></div>
                        </div>
                    @endif
                    @if ($evModePct !== null)
                        <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                            <div class="text-xs text-zinc-500">EV mode podíl</div>
                            <div class="text-base font-semibold mt-0.5">{{ $evModePct }}<span class="text-xs text-zinc-500 font-normal ml-0.5">%</span></div>
                        </div>
                    @endif
                    @if ($hvTempMax !== null)
                        <div class="bg-zinc-50 dark:bg-zinc-950/50 rounded-lg p-3">
                            <div class="text-xs text-zinc-500">HV teplota max</div>
                            <div class="text-base font-semibold mt-0.5">{{ $hvTempMax }}<span class="text-xs text-zinc-500 font-normal ml-0.5">°C</span></div>
                        </div>
                    @endif
                </div>
            @else
                <div class="p-5 text-sm">
                    <div class="text-zinc-500 mb-2">
                        Auto je {{ $trip->vehicle->fuel_type_label }}, ale Teltonika zatím <strong>neposílá data o trakční baterii</strong>.
                    </div>
                    <div class="text-xs text-zinc-500 leading-relaxed">
                        Pro získání SOC, voltage, EV mode, charging status atd. je potřeba nakonfigurovat <strong>Teltonika Configurator</strong>
                        (USB session) s VAG-specific PIDs (Mode 0x22). Doporučené mapování na AVL ID 850–857.
                        Po setupu se zde objeví: SOC %, kWh spotřebováno, kWh/100km, EV mode podíl, HV teplota, charging status.
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Position log (collapsible) --}}
    @if ($positions->isNotEmpty())
        <details class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl group">
            <summary class="px-5 py-3 cursor-pointer flex items-center gap-2 text-sm font-medium">
                <i data-lucide="chevron-right" class="w-4 h-4 transition-transform group-open:rotate-90"></i>
                Surové pozice ({{ $positions->count() }})
            </summary>
            <div class="overflow-x-auto border-t border-zinc-200 dark:border-zinc-800">
                <table class="w-full text-xs">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium">Čas</th>
                            <th class="text-left px-4 py-2 font-medium">Lat / Lng</th>
                            <th class="text-right px-4 py-2 font-medium">GPS km/h</th>
                            <th class="text-right px-4 py-2 font-medium">OBD km/h</th>
                            <th class="text-right px-4 py-2 font-medium">RPM</th>
                            <th class="text-right px-4 py-2 font-medium">Palivo</th>
                            <th class="text-right px-4 py-2 font-medium">Chlad.</th>
                            <th class="text-right px-4 py-2 font-medium">12V</th>
                            <th class="text-right px-4 py-2 font-medium">Sat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                        @foreach ($positions as $p)
                            <tr>
                                <td class="px-4 py-1.5">{{ $p->recorded_at->format('H:i:s') }}</td>
                                <td class="px-4 py-1.5">{{ number_format($p->latitude, 5, '.', '') }}, {{ number_format($p->longitude, 5, '.', '') }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $p->speed }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $p->obd_speed ?? '—' }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $p->engine_rpm ?? '—' }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $p->fuel_level !== null ? $p->fuel_level . '%' : '—' }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $p->coolant_temp !== null ? $p->coolant_temp . '°' : '—' }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $p->external_voltage !== null ? number_format($p->external_voltage, 2, ',', '') : '—' }}</td>
                                <td class="px-4 py-1.5 text-right">{{ $p->satellites }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    @if (!empty($telemetry))
        @php
            $voltJson  = json_encode($voltageSeries);
            $rpmJson   = json_encode($rpmSeries);
            $speedJson = json_encode($speedSeries);
        @endphp
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@4"></script>
        <script>
            (function () {
                const isDark = document.documentElement.classList.contains('dark');
                const axis = isDark ? '#a1a1aa' : '#52525b';
                const grid = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
                const baseCfg = {
                    chart: { height: 160, toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: 'Inter,sans-serif', background: 'transparent', animations: { speed: 250 } },
                    theme: { mode: isDark ? 'dark' : 'light' },
                    grid: { borderColor: grid, strokeDashArray: 3 },
                    stroke: { curve: 'smooth', width: 2 },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    tooltip: { theme: isDark ? 'dark' : 'light' },
                };

                const volts = {!! $voltJson !!};
                if (volts.length) {
                    new ApexCharts(document.querySelector('#voltChart'), Object.assign({}, baseCfg, {
                        chart: Object.assign({}, baseCfg.chart, { type: 'area' }),
                        series: [{ name: 'V', data: volts.map(p => p.v) }],
                        xaxis: { categories: volts.map(p => p.t), labels: { style: { colors: axis, fontSize: '10px' }, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
                        yaxis: { labels: { style: { colors: axis, fontSize: '10px' }, formatter: v => v.toFixed(2) } },
                        colors: ['#3b82f6'],
                        fill: { type: 'gradient', gradient: { shadeIntensity: 0.6, opacityFrom: 0.35, opacityTo: 0 } },
                    })).render();
                }

                const rpms = {!! $rpmJson !!};
                if (rpms.length) {
                    new ApexCharts(document.querySelector('#rpmChart'), Object.assign({}, baseCfg, {
                        chart: Object.assign({}, baseCfg.chart, { type: 'area' }),
                        series: [{ name: 'rpm', data: rpms.map(p => p.v) }],
                        xaxis: { categories: rpms.map(p => p.t), labels: { style: { colors: axis, fontSize: '10px' }, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
                        yaxis: { labels: { style: { colors: axis, fontSize: '10px' } } },
                        colors: ['#f97316'],
                        fill: { type: 'gradient', gradient: { shadeIntensity: 0.6, opacityFrom: 0.35, opacityTo: 0 } },
                    })).render();
                }

                const spds = {!! $speedJson !!};
                if (spds.length) {
                    new ApexCharts(document.querySelector('#speedChart'), Object.assign({}, baseCfg, {
                        chart: Object.assign({}, baseCfg.chart, { type: 'line' }),
                        series: [
                            { name: 'GPS', data: spds.map(p => p.gps) },
                            { name: 'OBD', data: spds.map(p => p.obd) },
                        ],
                        xaxis: { categories: spds.map(p => p.t), labels: { style: { colors: axis, fontSize: '10px' }, rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
                        yaxis: { labels: { style: { colors: axis, fontSize: '10px' } } },
                        colors: ['#06b6d4', '#3b82f6'],
                        legend: { show: true, position: 'top', horizontalAlign: 'right', labels: { colors: axis }, fontSize: '11px', markers: { radius: 12 } },
                    })).render();
                }
            })();
        </script>
    @endif

    @if (count($polyline) >= 2)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function () {
                const points = {!! json_encode($polyline) !!};

                // Speed → color (km/h)
                function speedColor(kmh) {
                    if (kmh < 5)   return '#71717a'; // gray  (zastaveno)
                    if (kmh < 30)  return '#10b981'; // green
                    if (kmh < 60)  return '#06b6d4'; // cyan
                    if (kmh < 90)  return '#3b82f6'; // blue
                    if (kmh < 110) return '#f59e0b'; // amber
                    if (kmh < 130) return '#f97316'; // orange
                    return '#ef4444';                // red (rychlejší)
                }

                const map = L.map('trip-map', { zoomControl: true }).setView([points[0].lat, points[0].lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(map);

                // Draw colored segments (each pair of consecutive positions)
                const segments = [];
                for (let i = 1; i < points.length; i++) {
                    const a = points[i - 1], b = points[i];
                    const avgSpeed = (a.speed + b.speed) / 2;
                    const seg = L.polyline(
                        [[a.lat, a.lng], [b.lat, b.lng]],
                        { color: speedColor(avgSpeed), weight: 5, opacity: 0.92, lineJoin: 'round' }
                    ).addTo(map);
                    seg.bindTooltip(`${Math.round(avgSpeed)} km/h`, { sticky: true, direction: 'top' });
                    segments.push(seg);
                }

                const startIcon = L.divIcon({
                    className: '',
                    html: '<div style="width:14px;height:14px;border-radius:50%;background:#10b981;border:3px solid #fff;box-shadow:0 0 0 2px #10b981"></div>',
                    iconSize: [14, 14], iconAnchor: [7, 7],
                });
                const endIcon = L.divIcon({
                    className: '',
                    html: '<div style="width:14px;height:14px;border-radius:50%;background:#ef4444;border:3px solid #fff;box-shadow:0 0 0 2px #ef4444"></div>',
                    iconSize: [14, 14], iconAnchor: [7, 7],
                });

                L.marker([points[0].lat, points[0].lng], { icon: startIcon }).addTo(map).bindPopup('<b>Start</b>');
                const last = points[points.length - 1];
                L.marker([last.lat, last.lng], { icon: endIcon }).addTo(map).bindPopup('<b>Konec</b>');

                // Legend
                const legend = L.control({ position: 'bottomright' });
                legend.onAdd = function () {
                    const div = L.DomUtil.create('div');
                    div.innerHTML = `
                        <div style="background:rgba(15,23,42,0.9);color:#fff;padding:8px 10px;border-radius:8px;font-size:11px;line-height:1.55;box-shadow:0 4px 12px rgba(0,0,0,0.25)">
                            <div style="font-weight:600;margin-bottom:4px">Rychlost</div>
                            <div><span style="display:inline-block;width:18px;height:3px;background:#71717a;vertical-align:middle;margin-right:6px"></span>stojí (&lt; 5)</div>
                            <div><span style="display:inline-block;width:18px;height:3px;background:#10b981;vertical-align:middle;margin-right:6px"></span>5 – 30 km/h</div>
                            <div><span style="display:inline-block;width:18px;height:3px;background:#06b6d4;vertical-align:middle;margin-right:6px"></span>30 – 60 km/h</div>
                            <div><span style="display:inline-block;width:18px;height:3px;background:#3b82f6;vertical-align:middle;margin-right:6px"></span>60 – 90 km/h</div>
                            <div><span style="display:inline-block;width:18px;height:3px;background:#f59e0b;vertical-align:middle;margin-right:6px"></span>90 – 110 km/h</div>
                            <div><span style="display:inline-block;width:18px;height:3px;background:#f97316;vertical-align:middle;margin-right:6px"></span>110 – 130 km/h</div>
                            <div><span style="display:inline-block;width:18px;height:3px;background:#ef4444;vertical-align:middle;margin-right:6px"></span>&gt; 130 km/h</div>
                        </div>`;
                    return div;
                };
                legend.addTo(map);

                // Fit bounds across all segments
                if (segments.length) {
                    const group = L.featureGroup(segments);
                    map.fitBounds(group.getBounds(), { padding: [30, 30] });
                }
            })();
        </script>
    @endif
@endsection
