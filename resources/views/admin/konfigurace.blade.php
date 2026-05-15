@extends('layouts.app', ['title' => 'Konfigurace'])
@section('header', 'Konfigurace')

@section('content')
    <div class="max-w-3xl space-y-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6">
            <h3 class="text-sm font-semibold mb-1">Aplikace</h3>
            <p class="text-xs text-zinc-500 mb-4">Načteno z <code class="bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">.env</code> / runtime configu.</p>
            <dl class="text-sm space-y-2 text-zinc-700 dark:text-zinc-300">
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">Verze Laravel</dt><dd class="font-mono">{{ app()->version() }}</dd></div>
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">PHP</dt><dd class="font-mono">{{ PHP_VERSION }}</dd></div>
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">Locale</dt><dd>{{ app()->getLocale() }}</dd></div>
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">Timezone</dt><dd>{{ config('app.timezone') }}</dd></div>
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">DB</dt><dd>{{ config('database.connections.' . config('database.default') . '.driver') }}</dd></div>
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">Cache</dt><dd>{{ config('cache.default') }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Queue</dt><dd>{{ config('queue.default') }}</dd></div>
            </dl>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6">
            <h3 class="text-sm font-semibold mb-1">Integrace</h3>
            <dl class="text-sm space-y-2 text-zinc-700 dark:text-zinc-300">
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">Mapy</dt><dd>OpenStreetMap (Leaflet)</dd></div>
                <div class="flex justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2"><dt class="text-zinc-500">Reverse geocoding</dt><dd>Nominatim, cs locale, 30 d cache</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">GPS protokol</dt><dd>Teltonika Codec 8 / 8 Ext</dd></div>
            </dl>
        </div>

        <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4 text-sm text-amber-700 dark:text-amber-400">
            <div class="flex items-start gap-2">
                <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0"></i>
                <div>Editace runtime konfigurace přes UI bude přidána v další iteraci. Pro teď viz <code class="font-mono">/home/docker/gpsmanager/gpsmanager_app/.env</code>.</div>
            </div>
        </div>
    </div>
@endsection
