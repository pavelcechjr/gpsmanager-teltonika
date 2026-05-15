@extends('layouts.app', ['title' => 'Řidiči'])
@section('header', 'Řidiči')

@section('header-actions')
    <x-btn :href="route('ridici.create')" icon="plus">Nový řidič</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <form method="GET" class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Hledat jméno, email, telefon..."
                       class="w-full pl-9 pr-3 py-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <select name="status" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                <option value="">Všichni</option>
                <option value="active"   @selected(request('status')==='active')>Jen aktivní</option>
                <option value="inactive" @selected(request('status')==='inactive')>Jen neaktivní</option>
            </select>
            <x-btn type="submit" variant="secondary" icon="filter">Filtrovat</x-btn>
            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route('ridici.index') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Resetovat</a>
            @endif
        </form>

        @if ($drivers->isEmpty())
            <x-empty-state icon="users" title="Žádní řidiči" description="{{ request()->hasAny(['q','status']) ? 'Filtru neodpovídá žádný řidič.' : 'Začni přidáním prvního řidiče.' }}">
                @unless (request()->hasAny(['q','status']))
                    <x-btn :href="route('ridici.create')" icon="plus">Přidat řidiče</x-btn>
                @endunless
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left  px-4 py-3 font-medium">Jméno</th>
                            <th class="text-left  px-4 py-3 font-medium">Kontakt</th>
                            <th class="text-left  px-4 py-3 font-medium">Vozidla</th>
                            <th class="text-left  px-4 py-3 font-medium">Status</th>
                            <th class="text-right px-4 py-3 font-medium">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($drivers as $driver)
                            @php
                                $ini = strtoupper(mb_substr($driver->first_name, 0, 1) . mb_substr($driver->last_name, 0, 1));
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-semibold text-white shrink-0">{{ $ini }}</div>
                                        <div class="min-w-0">
                                            <div class="font-medium truncate">{{ $driver->full_name }}</div>
                                            @if($driver->note)
                                                <div class="text-xs text-zinc-500 truncate max-w-xs">{{ $driver->note }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($driver->email)
                                        <a href="mailto:{{ $driver->email }}" class="text-zinc-700 dark:text-zinc-300 hover:text-indigo-500 block truncate">{{ $driver->email }}</a>
                                    @endif
                                    @if($driver->phone)
                                        <a href="tel:{{ $driver->phone }}" class="text-zinc-500 text-xs hover:text-indigo-500 block">{{ $driver->phone }}</a>
                                    @endif
                                    @if (!$driver->email && !$driver->phone)
                                        <span class="text-zinc-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($driver->vehicles_as_default_count > 0)
                                        <x-badge variant="indigo">{{ $driver->vehicles_as_default_count }} vozidel</x-badge>
                                    @else
                                        <span class="text-zinc-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($driver->active)
                                        <x-badge variant="green">Aktivní</x-badge>
                                    @else
                                        <x-badge variant="gray">Neaktivní</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('ridici.show', $driver) }}" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Detail řidiče + jeho jízdy">
                                            <i data-lucide="user-square" class="w-4 h-4 text-zinc-500"></i>
                                        </a>
                                        <a href="{{ route('ridici.edit', $driver) }}" class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md" title="Upravit">
                                            <i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i>
                                        </a>
                                        <form method="POST" action="{{ route('ridici.destroy', $driver) }}"
                                              onsubmit="return confirm('Opravdu smazat řidiče {{ $driver->full_name }}?');" class="inline">
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
            @if ($drivers->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 text-sm">
                    {{ $drivers->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
