@extends('layouts.app', ['title' => 'Zařízení'])
@section('header', 'Zařízení (Teltonika jednotky)')

@section('header-actions')
    <x-btn :href="route('zarizeni.create')" icon="plus">Nové zařízení</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <form method="GET" class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Hledat IMEI, telefon, model..."
                       class="w-full pl-9 pr-3 py-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <select name="state" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                <option value="">Vše</option>
                <option value="online"  @selected(request('state')==='online')>Online</option>
                <option value="offline" @selected(request('state')==='offline')>Offline</option>
            </select>
            <select name="status" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                <option value="">Všechny</option>
                <option value="active"   @selected(request('status')==='active')>Aktivní</option>
                <option value="inactive" @selected(request('status')==='inactive')>Neaktivní</option>
            </select>
            <x-btn type="submit" variant="secondary" icon="filter">Filtrovat</x-btn>
            @if (request()->hasAny(['q', 'status', 'state']))
                <a href="{{ route('zarizeni.index') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Resetovat</a>
            @endif
        </form>

        @if ($devices->isEmpty())
            <x-empty-state icon="satellite" title="Žádná zařízení" description="{{ request()->hasAny(['q','status','state']) ? 'Filtru neodpovídá žádné zařízení.' : 'Začni přidáním první Teltonika jednotky (IMEI, SIM tel. číslo, model).' }}">
                @unless (request()->hasAny(['q','status','state']))
                    <x-btn :href="route('zarizeni.create')" icon="plus">Přidat zařízení</x-btn>
                @endunless
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left  px-4 py-3 font-medium">IMEI / Model</th>
                            <th class="text-left  px-4 py-3 font-medium">SIM telefon</th>
                            <th class="text-left  px-4 py-3 font-medium">Vozidlo</th>
                            <th class="text-left  px-4 py-3 font-medium">Naposledy slyšeno</th>
                            <th class="text-left  px-4 py-3 font-medium">Stav</th>
                            <th class="text-right px-4 py-3 font-medium">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($devices as $device)
                            @php
                                $online = $device->last_seen_at && $device->last_seen_at->gt(now()->subMinutes(5));
                                $hasContact = (bool) $device->last_seen_at;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-purple-500/10 flex items-center justify-center shrink-0">
                                            <i data-lucide="satellite" class="w-5 h-5 text-purple-500"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-mono text-sm font-medium truncate">{{ $device->imei }}</div>
                                            <div class="text-xs text-zinc-500">{{ $device->model ?: '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($device->phone_number)
                                        <a href="tel:{{ $device->phone_number }}" class="text-zinc-700 dark:text-zinc-300 hover:text-indigo-500">{{ $device->phone_number }}</a>
                                    @else
                                        <span class="text-zinc-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($device->vehicle)
                                        <a href="{{ route('vozidla.show', $device->vehicle) }}" class="text-indigo-500 hover:text-indigo-600">
                                            {{ $device->vehicle->name ?: $device->vehicle->plate }}
                                            @if ($device->vehicle->plate)
                                                <span class="text-zinc-500 text-xs">({{ $device->vehicle->plate }})</span>
                                            @endif
                                        </a>
                                        @if ($device->vehicle->is_eco)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">
                                                    <i data-lucide="leaf" class="w-3 h-3"></i>
                                                    <span>{{ $device->vehicle->fuel_type_short }}</span>
                                                </span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-zinc-400 text-xs">nepřiřazeno</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                    @if ($hasContact)
                                        <span title="{{ $device->last_seen_at->format('d.m.Y H:i:s') }}">
                                            {{ $device->last_seen_at->diffForHumans(['short' => true]) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 text-xs">Nepřipojeno</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (!$device->active)
                                        <x-badge variant="gray">Neaktivní</x-badge>
                                    @elseif ($online)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Online
                                        </span>
                                    @elseif ($hasContact)
                                        <x-badge variant="amber">Offline</x-badge>
                                    @else
                                        <x-badge variant="gray">Nepřipojeno</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('zarizeni.edit', $device) }}" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Upravit">
                                            <i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i>
                                        </a>
                                        <form method="POST" action="{{ route('zarizeni.destroy', $device) }}"
                                              onsubmit="return confirm('Opravdu smazat zařízení {{ $device->imei }}?');" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 hover:bg-red-500/10 rounded-md" title="Smazat">
                                                <i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($devices->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 text-sm">
                    {{ $devices->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
