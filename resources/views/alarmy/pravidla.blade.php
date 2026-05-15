@extends('layouts.app', ['title' => 'Pravidla alarmů'])
@section('header', 'Pravidla alarmů')

@section('header-actions')
    <x-btn :href="route('alarmy.pravidla.create')" icon="plus">Nové pravidlo</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        @if ($rules->isEmpty())
            <x-empty-state icon="settings" title="Žádná pravidla" description="Vytvoř první pravidlo — např. překročení rychlosti nad 130 km/h, slabou 12V baterii, atd.">
                <x-btn :href="route('alarmy.pravidla.create')" icon="plus">Nové pravidlo</x-btn>
            </x-empty-state>
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left  px-4 py-2 font-medium">Název</th>
                        <th class="text-left  px-4 py-2 font-medium">Typ</th>
                        <th class="text-left  px-4 py-2 font-medium">Vozidlo</th>
                        <th class="text-left  px-4 py-2 font-medium">Závažnost</th>
                        <th class="text-left  px-4 py-2 font-medium">Notify</th>
                        <th class="text-left  px-4 py-2 font-medium">Stav</th>
                        <th class="text-right px-4 py-2 font-medium">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($rules as $r)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-2 font-medium">{{ $r->name }}</td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $r->type_label }}</td>
                            <td class="px-4 py-2">{{ $r->vehicle ? $r->vehicle->name . ' (' . $r->vehicle->plate . ')' : '— všechna —' }}</td>
                            <td class="px-4 py-2">
                                @switch($r->severity)
                                    @case('critical') <x-badge variant="red">Kritické</x-badge> @break
                                    @case('warn')     <x-badge variant="amber">Varování</x-badge> @break
                                    @default          <x-badge variant="blue">Info</x-badge>
                                @endswitch
                            </td>
                            <td class="px-4 py-2 text-xs text-zinc-500">
                                @if ($r->notify_emails)
                                    {{ implode(', ', array_slice($r->notify_emails, 0, 2)) }}{{ count($r->notify_emails) > 2 ? '…' : '' }}
                                @else — @endif
                            </td>
                            <td class="px-4 py-2">
                                @if ($r->active) <x-badge variant="green">Aktivní</x-badge> @else <x-badge variant="gray">Vypnuto</x-badge> @endif
                            </td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('alarmy.pravidla.edit', $r) }}" class="p-1.5 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded"><i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i></a>
                                <form method="POST" action="{{ route('alarmy.pravidla.destroy', $r) }}" class="inline" onsubmit="return confirm('Smazat pravidlo {{ $r->name }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 inline-block hover:bg-red-500/10 rounded"><i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
