@extends('layouts.app', ['title' => 'Statistiky'])
@section('header', 'Statistiky knihy jízd')

@section('header-actions')
    @php
        $ranges = [
            'week'  => 'Týden',
            'month' => 'Měsíc',
            'year'  => 'Rok',
            'all'   => 'Vše',
        ];
    @endphp
    <div class="inline-flex bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg p-1">
        @foreach ($ranges as $key => $name)
            <a href="{{ route('kniha-jizd.statistiky', ['range' => $key]) }}"
               class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $rangeKey === $key ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">
                {{ $name }}
            </a>
        @endforeach
    </div>
@endsection

@section('content')
    <p class="text-xs text-zinc-500 mb-4">{{ $label }} ({{ $from->locale('cs_CZ')->isoFormat('D.M.YYYY') }} – {{ $to->locale('cs_CZ')->isoFormat('D.M.YYYY') }})</p>

    {{-- Totals --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Jízd</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['trips'], 0, ',', ' ') }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Najeto</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['km'], 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Doba jízd</div>
            <div class="text-2xl font-semibold mt-1">
                {{ intdiv($totals['minutes'], 60) }} <span class="text-sm text-zinc-500 font-normal">h</span>
                {{ str_pad((string) ($totals['minutes'] % 60), 2, '0', STR_PAD_LEFT) }} <span class="text-sm text-zinc-500 font-normal">min</span>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase tracking-wide">Max rychlost</div>
            <div class="text-2xl font-semibold mt-1">{{ $totals['max_kmh'] }} <span class="text-sm text-zinc-500 font-normal">km/h</span></div>
        </div>
    </div>

    {{-- Trend chart (always 30 days regardless of range) --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 mb-4">
        <div class="flex items-center justify-between mb-1">
            <div>
                <h3 class="text-sm font-semibold tracking-tight">Najetá vzdálenost</h3>
                <p class="text-xs text-zinc-500">Posledních 30 dní</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-semibold">{{ number_format(array_sum($trendKm), 0, ',', ' ') }} km</div>
                <div class="text-xs text-zinc-500">{{ array_sum($trendTrips) }} jízd</div>
            </div>
        </div>
        <div id="trendChart" class="-mx-2"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Per driver --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800">
                <h3 class="text-sm font-semibold tracking-tight">Podle řidiče</h3>
                <p class="text-xs text-zinc-500">{{ $perDriver->count() }} řidičů s ≥ 1 jízdou</p>
            </div>
            @if ($perDriver->isEmpty())
                <div class="p-8 text-center text-sm text-zinc-500">Žádná data v zadaném období.</div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">Řidič</th>
                            <th class="text-right px-5 py-2 font-medium">Jízd</th>
                            <th class="text-right px-5 py-2 font-medium">km</th>
                            <th class="text-right px-5 py-2 font-medium">Doba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($perDriver as $row)
                            <tr>
                                <td class="px-5 py-2">{{ $row->driver?->full_name ?? '— neznámý —' }}</td>
                                <td class="px-5 py-2 text-right">{{ $row->trips }}</td>
                                <td class="px-5 py-2 text-right">{{ number_format($row->meters / 1000, 1, ',', ' ') }}</td>
                                <td class="px-5 py-2 text-right">{{ intdiv($row->seconds, 3600) }}:{{ str_pad((string) intdiv($row->seconds % 3600, 60), 2, '0', STR_PAD_LEFT) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Per vehicle --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800">
                <h3 class="text-sm font-semibold tracking-tight">Podle vozidla</h3>
                <p class="text-xs text-zinc-500">{{ $perVehicle->count() }} vozidel s ≥ 1 jízdou</p>
            </div>
            @if ($perVehicle->isEmpty())
                <div class="p-8 text-center text-sm text-zinc-500">Žádná data v zadaném období.</div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">Vozidlo</th>
                            <th class="text-right px-5 py-2 font-medium">Jízd</th>
                            <th class="text-right px-5 py-2 font-medium">km</th>
                            <th class="text-right px-5 py-2 font-medium">Max km/h</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($perVehicle as $row)
                            <tr>
                                <td class="px-5 py-2">
                                    <div class="font-medium">{{ $row->vehicle?->name ?? '—' }}</div>
                                    <div class="text-xs text-zinc-500 font-mono">{{ $row->vehicle?->plate }}</div>
                                </td>
                                <td class="px-5 py-2 text-right">{{ $row->trips }}</td>
                                <td class="px-5 py-2 text-right">{{ number_format($row->meters / 1000, 1, ',', ' ') }}</td>
                                <td class="px-5 py-2 text-right">{{ (int) ($row->max_speed ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @php
        $trendKmJson    = json_encode($trendKm);
        $trendTripsJson = json_encode($trendTrips);
        $trendLblJson   = json_encode($trendLabels);
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@4"></script>
    <script>
        (function () {
            const isDark    = document.documentElement.classList.contains('dark');
            const axisColor = isDark ? '#a1a1aa' : '#52525b';
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            const km        = {!! $trendKmJson !!};
            const tr        = {!! $trendTripsJson !!};
            const labels    = {!! $trendLblJson !!};
            new ApexCharts(document.querySelector('#trendChart'), {
                chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', background: 'transparent' },
                theme: { mode: isDark ? 'dark' : 'light' },
                series: [
                    { name: 'km',   type: 'area', data: km },
                    { name: 'jízd', type: 'line', data: tr },
                ],
                xaxis: { categories: labels, labels: { style: { colors: axisColor, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
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
        })();
    </script>
@endsection
