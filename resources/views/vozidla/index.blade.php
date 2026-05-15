@extends('layouts.app', ['title' => 'Vozidla'])
@section('header', 'Vozidla')

@section('header-actions')
    <x-btn :href="route('vozidla.create')" icon="plus">Nové vozidlo</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <form method="GET" class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Hledat název nebo SPZ..."
                       class="w-full pl-9 pr-3 py-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <select name="status" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                <option value="">Všechna</option>
                <option value="active"   @selected(request('status')==='active')>Jen aktivní</option>
                <option value="inactive" @selected(request('status')==='inactive')>Jen neaktivní</option>
            </select>
            <x-btn type="submit" variant="secondary" icon="filter">Filtrovat</x-btn>
            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route('vozidla.index') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Resetovat</a>
            @endif
        </form>

        @if ($vehicles->isEmpty())
            <x-empty-state icon="truck" title="Žádná vozidla" description="{{ request()->hasAny(['q','status']) ? 'Filtru neodpovídá žádné vozidlo.' : 'Začni přidáním vozidla — přiřaď default řidiče a Teltonika jednotku.' }}">
                @unless (request()->hasAny(['q','status']))
                    <x-btn :href="route('vozidla.create')" icon="plus">Přidat vozidlo</x-btn>
                @endunless
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left  px-4 py-3 font-medium">Vozidlo</th>
                            <th class="text-left  px-4 py-3 font-medium">SPZ</th>
                            <th class="text-left  px-4 py-3 font-medium">Default řidič</th>
                            <th class="text-left  px-4 py-3 font-medium">Jednotka</th>
                            <th class="text-left  px-4 py-3 font-medium">Status</th>
                            <th class="text-right px-4 py-3 font-medium">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($vehicles as $vehicle)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                                            <i data-lucide="truck" class="w-5 h-5 text-blue-500"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <div class="font-medium truncate">{{ $vehicle->name }}</div>
                                                @if ($vehicle->is_eco)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-emerald-500/15 text-emerald-500 border border-emerald-500/20 shrink-0">
                                                        <i data-lucide="leaf" class="w-3 h-3"></i>
                                                        <span>{{ $vehicle->fuel_type_short }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($vehicle->color)
                                                <div class="text-xs text-zinc-500">{{ $vehicle->color }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-sm font-mono font-medium tracking-wide">
                                        {{ $vehicle->plate }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($vehicle->defaultDriver)
                                        {{ $vehicle->defaultDriver->full_name }}
                                    @else
                                        <span class="text-zinc-400 text-xs">— nepřiřazen —</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($vehicle->device)
                                        <div class="font-mono text-xs">{{ $vehicle->device->imei }}</div>
                                        @if ($vehicle->device->model)
                                            <div class="text-xs text-zinc-500">{{ $vehicle->device->model }}</div>
                                        @endif
                                    @else
                                        <span class="text-zinc-400 text-xs">— bez jednotky —</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($vehicle->active)
                                        <x-badge variant="green">Aktivní</x-badge>
                                    @else
                                        <x-badge variant="gray">Neaktivní</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('vozidla.show', $vehicle) }}" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Detail vozidla (km, telemetrie)">
                                            <i data-lucide="line-chart" class="w-4 h-4 text-zinc-500"></i>
                                        </a>
                                        <a href="{{ route('vozidla.edit', $vehicle) }}" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Upravit">
                                            <i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i>
                                        </a>
                                        <form method="POST" action="{{ route('vozidla.destroy', $vehicle) }}"
                                              onsubmit="return confirm('Opravdu smazat vozidlo {{ $vehicle->name }} ({{ $vehicle->plate }})?');" class="inline">
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
            @if ($vehicles->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 text-sm">
                    {{ $vehicles->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
