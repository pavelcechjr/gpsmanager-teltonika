@extends('layouts.app', ['title' => 'Servery'])
@section('header', 'Servery a listenery')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i data-lucide="satellite-dish" class="w-5 h-5 text-emerald-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold">Teltonika listener</h3>
                        <p class="text-xs text-zinc-500">Codec 8 / Codec 8 Extended TCP server</p>
                    </div>
                </div>
                <x-badge variant="green">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span>
                    běží
                </x-badge>
            </div>
            <dl class="text-sm space-y-1.5 mt-4 text-zinc-700 dark:text-zinc-300">
                <div class="flex justify-between"><dt class="text-zinc-500">Port</dt><dd class="font-mono">5027 TCP</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Bind</dt><dd class="font-mono">0.0.0.0:5027</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Proces</dt><dd>supervisor / auto-restart</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Aktivních zařízení</dt><dd>{{ $stats['active_devices'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Pozic za 24 h</dt><dd>{{ number_format($stats['positions_24h'], 0, ',', ' ') }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Otevřené jízdy</dt><dd>{{ $stats['trips_open'] }}</dd></div>
            </dl>
            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-500">
                Restart listeneru: <code class="bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">supervisorctl restart teltonika-listener</code>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5 text-blue-500"></i>
                </div>
                <div>
                    <h3 class="font-semibold">Trip closer (cron)</h3>
                    <p class="text-xs text-zinc-500">Zavírá jízdy bez nové pozice &gt; 5 min</p>
                </div>
            </div>
            <dl class="text-sm space-y-1.5 mt-4 text-zinc-700 dark:text-zinc-300">
                <div class="flex justify-between"><dt class="text-zinc-500">Interval</dt><dd>každou minutu</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Příkaz</dt><dd class="font-mono text-xs">gpsmanager:close-stale-trips</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Funkce</dt><dd>Haversine, max_speed, geocoding</dd></div>
            </dl>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 mt-4">
        <h3 class="text-sm font-semibold mb-3">Logy & monitoring</h3>
        <div class="text-xs text-zinc-500 space-y-1">
            <div><code class="bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">/var/log/supervisor/listener.log</code> — output listeneru</div>
            <div><code class="bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">/var/www/html/storage/logs/laravel.log</code> — aplikační log (parsed packety, errory)</div>
            <div><code class="bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">/var/log/laravel-schedule.log</code> — cron schedule output</div>
        </div>
    </div>
@endsection
