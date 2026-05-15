@extends('layouts.app', ['title' => 'Údržba'])
@section('header', 'Údržba')

@section('header-actions')
    <x-btn :href="route('udrzba.create')" icon="plus">Přidat úkon</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <form method="GET" class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-2">
            <select name="vehicle_id" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                <option value="">Všechna vozidla</option>
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}" @selected(request('vehicle_id') == $v->id)>{{ $v->name }} ({{ $v->plate }})</option>
                @endforeach
            </select>
            <select name="status" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                <option value="">Všechny</option>
                <option value="planned" @selected(request('status') === 'planned')>Plánované</option>
                <option value="done"    @selected(request('status') === 'done')>Provedené</option>
            </select>
            <x-btn type="submit" variant="secondary" icon="filter">Filtrovat</x-btn>
        </form>

        @if ($maintenances->isEmpty())
            <x-empty-state icon="wrench" title="Žádné úkony údržby">
                <x-btn :href="route('udrzba.create')" icon="plus">Přidat úkon</x-btn>
            </x-empty-state>
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium">Typ</th>
                        <th class="text-left px-4 py-2 font-medium">Vozidlo</th>
                        <th class="text-left px-4 py-2 font-medium">Termín</th>
                        <th class="text-left px-4 py-2 font-medium">Provedeno</th>
                        <th class="text-right px-4 py-2 font-medium">Cena</th>
                        <th class="text-left px-4 py-2 font-medium">Dodavatel</th>
                        <th class="text-left px-4 py-2 font-medium">Stav</th>
                        <th class="text-right px-4 py-2 font-medium">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($maintenances as $m)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-2 font-medium">{{ $m->type_label }}</td>
                            <td class="px-4 py-2">{{ $m->vehicle?->name }} <span class="text-xs text-zinc-500 font-mono">{{ $m->vehicle?->plate }}</span></td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $m->planned_at?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $m->performed_at?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ $m->price ? number_format($m->price, 2, ',', ' ') . ' Kč' : '—' }}</td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $m->supplier ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @switch($m->status)
                                    @case('done')    <x-badge variant="green">Hotovo</x-badge> @break
                                    @case('overdue') <x-badge variant="red">Po termínu</x-badge> @break
                                    @case('planned') <x-badge variant="amber">Plánováno</x-badge> @break
                                    @default        <x-badge variant="gray">Návrh</x-badge>
                                @endswitch
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('udrzba.edit', $m) }}" class="p-1.5 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded"><i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i></a>
                                <form method="POST" action="{{ route('udrzba.destroy', $m) }}" class="inline" onsubmit="return confirm('Smazat?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 inline-block hover:bg-red-500/10 rounded"><i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($maintenances->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">{{ $maintenances->links() }}</div>
            @endif
        @endif
    </div>
@endsection
