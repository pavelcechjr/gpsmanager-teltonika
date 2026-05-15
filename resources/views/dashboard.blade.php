@extends('layouts.app', ['title' => 'Dashboard'])
@section('header', 'Dashboard')

@section('header-actions')
    <span class="text-xs text-zinc-500 hidden sm:inline">{{ now()->locale('cs_CZ')->isoFormat('dddd D. MMMM YYYY') }}</span>
@endsection

@section('content')
    {{-- ─── KPI row ───────────────────────────────────────────────── --}}
    @php
        $cards = [
            [
                'label'  => 'Aktivní jízdy',
                'value'  => $kpi['active_trips'],
                'unit'   => null,
                'icon'   => 'play-circle',
                'tone'   => 'from-emerald-500 to-teal-500',
                'sub'    => 'právě probíhá',
                'route'  => 'kniha-jizd.index',
            ],
            [
                'label'  => 'Jízd dnes',
                'value'  => $kpi['trips_today'],
                'unit'   => null,
                'icon'   => 'route',
                'tone'   => 'from-indigo-500 to-violet-500',
                'sub'    => 'včetně probíhajících',
                'route'  => 'kniha-jizd.index',
            ],
            [
                'label'  => 'Najeto dnes',
                'value'  => number_format($kpi['km_today'], 0, ',', ' '),
                'unit'   => 'km',
                'icon'   => 'gauge',
                'tone'   => 'from-amber-500 to-orange-500',
                'sub'    => 'součet uzavřených jízd',
                'route'  => 'kniha-jizd.index',
            ],
            [
                'label'  => 'Jednotky online',
                'value'  => $kpi['online_devs'],
                'unit'   => '/ ' . $kpi['total_active_devs'],
                'icon'   => 'satellite',
                'tone'   => 'from-cyan-500 to-blue-500',
                'sub'    => 'posledních 5 minut',
                'route'  => 'zarizeni.index',
            ],
        ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        @foreach ($cards as $c)
            <a href="{{ route($c['route']) }}"
               class="group bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <div class="text-xs text-zinc-500 uppercase tracking-wide">{{ $c['label'] }}</div>
                        <div class="mt-1 flex items-baseline gap-1">
                            <span class="text-3xl font-semibold tracking-tight">{{ $c['value'] }}</span>
                            @if($c['unit'])<span class="text-sm text-zinc-500 font-medium">{{ $c['unit'] }}</span>@endif
                        </div>
                        <div class="text-xs text-zinc-500 mt-1 truncate">{{ $c['sub'] }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $c['tone'] }} flex items-center justify-center shadow-lg shrink-0">
                        <i data-lucide="{{ $c['icon'] }}" class="w-5 h-5 text-white"></i>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ─── Charts row ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-1">
                <div>
                    <h3 class="text-sm font-semibold tracking-tight">Najeté km</h3>
                    <p class="text-xs text-zinc-500">Posledních 14 dní (uzavřené jízdy)</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-semibold">{{ number_format(array_sum($kmDaily), 0, ',', ' ') }}</div>
                    <div class="text-xs text-zinc-500">km celkem · {{ array_sum($tripsDaily) }} jízd</div>
                </div>
            </div>
            <div id="kmChart" class="-mx-2"></div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold tracking-tight mb-1">Status jednotek</h3>
            <p class="text-xs text-zinc-500 mb-2">Rozdělení aktivních + neaktivních</p>
            <div id="statusChart" class="-mx-2"></div>
            <div class="text-xs text-zinc-500 mt-2 space-y-1">
                <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Online</span><span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $devStatus['online'] }}</span></div>
                <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Offline (24 h)</span><span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $devStatus['recent'] }}</span></div>
                <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-zinc-500"></span>Stale (&gt; 1 den)</span><span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $devStatus['stale'] }}</span></div>
                <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-zinc-700"></span>Nepřipojeno</span><span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $devStatus['never'] }}</span></div>
                <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500/70"></span>Neaktivní</span><span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $devStatus['inactive'] }}</span></div>
            </div>
        </div>
    </div>

    {{-- ─── Charts row 2 + online --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold tracking-tight mb-1">Top vozidla (posledních 7 dní)</h3>
            <p class="text-xs text-zinc-500 mb-3">Podle počtu jízd</p>
            @if ($topVehicles->isEmpty())
                <div class="text-sm text-zinc-500 py-8 text-center">Zatím žádné jízdy.</div>
            @else
                <div id="topChart" class="-mx-2"></div>
            @endif
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold tracking-tight">Právě online</h3>
                <span class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    live
                </span>
            </div>
            @if ($onlineVehicles->isEmpty())
                <div class="text-sm text-zinc-500 py-6 text-center">Žádné vozidlo není teď online.</div>
            @else
                <div class="space-y-2">
                    @foreach ($onlineVehicles as $v)
                        <div class="flex items-center gap-3 px-2 py-1.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium truncate">{{ $v->name }}</div>
                                <div class="text-xs text-zinc-500 font-mono truncate">{{ $v->plate }} · {{ $v->device?->imei }}</div>
                            </div>
                            <span class="text-xs text-zinc-500 shrink-0">{{ $v->device?->last_seen_at?->diffForHumans(['short' => true]) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Recent activity ────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h3 class="text-sm font-semibold tracking-tight">Poslední jízdy</h3>
                <p class="text-xs text-zinc-500">Nejnovějších {{ $recentTrips->count() }}</p>
            </div>
            <a href="{{ route('kniha-jizd.index') }}" class="text-xs text-indigo-500 hover:text-indigo-600 inline-flex items-center gap-1">
                Otevřít Knihu jízd <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
        @if ($recentTrips->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-zinc-500">Zatím žádné jízdy. Až se Teltonika jednotka začne připojovat, jízdy se objeví zde.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-5 py-2.5 font-medium">Vozidlo</th>
                            <th class="text-left px-5 py-2.5 font-medium">Zahájení</th>
                            <th class="text-left px-5 py-2.5 font-medium">Trasa</th>
                            <th class="text-left px-5 py-2.5 font-medium">Vzdálenost</th>
                            <th class="text-left px-5 py-2.5 font-medium">Řidič</th>
                            <th class="text-left px-5 py-2.5 font-medium">Stav</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($recentTrips as $t)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-5 py-2.5">
                                    <div class="font-medium truncate">{{ $t->vehicle?->name ?? '—' }}</div>
                                    <div class="text-xs text-zinc-500 font-mono">{{ $t->vehicle?->plate }}</div>
                                </td>
                                <td class="px-5 py-2.5 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                                    {{ $t->started_at?->format('d.m. H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-2.5 text-zinc-600 dark:text-zinc-400 max-w-xs">
                                    <div class="truncate">{{ $t->start_address ?? '—' }} →</div>
                                    <div class="truncate text-xs">{{ $t->end_address ?? '— probíhá —' }}</div>
                                </td>
                                <td class="px-5 py-2.5 whitespace-nowrap">
                                    @if ($t->distance_meters)
                                        {{ number_format($t->distance_meters / 1000, 1, ',', ' ') }} km
                                    @else
                                        <span class="text-zinc-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-2.5">{{ $t->driver?->full_name ?? '—' }}</td>
                                <td class="px-5 py-2.5">
                                    @if ($t->ended_at)
                                        <x-badge variant="green">Ukončeno</x-badge>
                                    @else
                                        <x-badge variant="amber">Probíhá</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @php
        $statusSeries = [$devStatus['online'], $devStatus['recent'], $devStatus['stale'], $devStatus['never'], $devStatus['inactive']];
        $topNames = $topVehicles->map(fn ($r) => $r->vehicle?->name ?? ('#' . $r->vehicle_id))->all();
        $topTripCounts = $topVehicles->pluck('trip_count')->map(fn ($v) => (int) $v)->all();
        $topKms = $topVehicles->pluck('total_m')->map(fn ($m) => (int) round(((int) $m) / 1000))->all();
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@4"></script>
    <script>
        (function () {
            const isDark    = document.documentElement.classList.contains('dark');
            const axisColor = isDark ? '#a1a1aa' : '#52525b';
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

            const kmDaily       = {!! json_encode($kmDaily) !!};
            const tripsDaily    = {!! json_encode($tripsDaily) !!};
            const kmLabels      = {!! json_encode($kmLabels) !!};
            const statusSeries  = {!! json_encode($statusSeries) !!};
            const topNames      = {!! json_encode($topNames) !!};
            const topTripCounts = {!! json_encode($topTripCounts) !!};
            const topKms        = {!! json_encode($topKms) !!};
            const hasTop        = topNames.length > 0;

            new ApexCharts(document.querySelector('#kmChart'), {
                chart: { type: 'area', height: 260, toolbar: { show: false }, animations: { speed: 400 }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
                theme: { mode: isDark ? 'dark' : 'light' },
                series: [
                    { name: 'km',    type: 'area', data: kmDaily },
                    { name: 'jízd',  type: 'line', data: tripsDaily },
                ],
                xaxis: { categories: kmLabels, labels: { style: { colors: axisColor, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: [
                    { labels: { style: { colors: axisColor, fontSize: '11px' }, formatter: v => Math.round(v) + ' km' } },
                    { opposite: true, labels: { style: { colors: axisColor, fontSize: '11px' }, formatter: v => Math.round(v) } },
                ],
                colors: ['#6366f1', '#f59e0b'],
                fill: { type: ['gradient', 'solid'], gradient: { shadeIntensity: 0.6, opacityFrom: 0.45, opacityTo: 0 } },
                stroke: { curve: 'smooth', width: [2, 2] },
                grid: { borderColor: gridColor, strokeDashArray: 3, padding: { left: 10, right: 10 } },
                tooltip: { theme: isDark ? 'dark' : 'light', shared: true, intersect: false },
                legend: { show: false },
                markers: { size: 0 },
            }).render();

            new ApexCharts(document.querySelector('#statusChart'), {
                chart: { type: 'donut', height: 200, fontFamily: 'Inter, sans-serif', background: 'transparent' },
                theme: { mode: isDark ? 'dark' : 'light' },
                series: statusSeries,
                labels: ['Online', 'Offline (24 h)', 'Stale', 'Nepřipojeno', 'Neaktivní'],
                colors: ['#10b981', '#f59e0b', '#71717a', '#3f3f46', '#ef4444'],
                stroke: { width: 0 },
                dataLabels: { enabled: false },
                legend: { show: false },
                plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Celkem', color: axisColor, formatter: () => statusSeries.reduce((a, b) => a + b, 0) } } } } },
                tooltip: { theme: isDark ? 'dark' : 'light' },
            }).render();

            if (hasTop) {
                new ApexCharts(document.querySelector('#topChart'), {
                    chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
                    theme: { mode: isDark ? 'dark' : 'light' },
                    series: [
                        { name: 'Počet jízd', data: topTripCounts },
                        { name: 'km',         data: topKms },
                    ],
                    xaxis: { categories: topNames, labels: { style: { colors: axisColor, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { style: { colors: axisColor, fontSize: '11px' } } },
                    colors: ['#6366f1', '#10b981'],
                    plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '50%' } },
                    stroke: { width: 0 },
                    dataLabels: { enabled: false },
                    grid: { borderColor: gridColor, strokeDashArray: 3 },
                    tooltip: { theme: isDark ? 'dark' : 'light' },
                    legend: { labels: { colors: axisColor } },
                }).render();
            }
        })();
    </script>
@endsection
