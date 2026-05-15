@extends('layouts.app', ['title' => 'Časové zóny'])
@section('header', 'Časové zóny (IANA)')

@section('content')
    <p class="text-xs text-zinc-500 mb-4">Výchozí timezone systému: <code class="bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">{{ config('app.timezone') }}</code>. Pro per-vozidlo / per-řidič nastavení použiješ identifikátor IANA (např. <code>Europe/Prague</code>).</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($zones as $region => $list)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-4 py-2 bg-zinc-50 dark:bg-zinc-950/50 border-b border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                    {{ $region }} ({{ $list->count() }})
                </div>
                <ul class="max-h-48 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800 text-xs font-mono">
                    @foreach ($list as $tz)
                        <li class="px-4 py-1.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 flex items-center justify-between">
                            <span>{{ $tz }}</span>
                            <span class="text-zinc-500">{{ (new \DateTime('now', new \DateTimeZone($tz)))->format('H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endsection
