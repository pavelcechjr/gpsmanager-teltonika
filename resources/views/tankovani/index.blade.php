@extends('layouts.app', ['title' => 'Tankování'])
@section('header', 'Tankování')

@section('header-actions')
    <x-btn :href="route('tankovani.create')" icon="plus">Přidat tankování</x-btn>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Tankování celkem</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['count'], 0, ',', ' ') }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Natankováno</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['liters'], 1, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">l</span></div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4">
            <div class="text-xs text-zinc-500 uppercase">Útrata</div>
            <div class="text-2xl font-semibold mt-1">{{ number_format($totals['total'], 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">Kč</span></div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <form method="GET" class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-2">
            <select name="vehicle_id" class="bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg px-3 py-2 text-sm">
                <option value="">Všechna vozidla</option>
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}" @selected(request('vehicle_id') == $v->id)>{{ $v->name }} ({{ $v->plate }})</option>
                @endforeach
            </select>
            <x-btn type="submit" variant="secondary" icon="filter">Filtrovat</x-btn>
        </form>

        @if ($refuelings->isEmpty())
            <x-empty-state icon="fuel" title="Žádné tankování" description="Zaznamenej první tankování pro evidenci PHM.">
                <x-btn :href="route('tankovani.create')" icon="plus">Přidat tankování</x-btn>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium">Datum</th>
                            <th class="text-left px-4 py-2 font-medium">Vozidlo</th>
                            <th class="text-left px-4 py-2 font-medium">Řidič</th>
                            <th class="text-right px-4 py-2 font-medium">Litrů</th>
                            <th class="text-right px-4 py-2 font-medium">Cena</th>
                            <th class="text-right px-4 py-2 font-medium">Kč/l</th>
                            <th class="text-right px-4 py-2 font-medium">Tachometr</th>
                            <th class="text-left px-4 py-2 font-medium">Stanice</th>
                            <th class="text-right px-4 py-2 font-medium">Akce</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($refuelings as $r)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-2 whitespace-nowrap">{{ $r->fueled_at->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $r->vehicle?->name }} <span class="text-xs text-zinc-500 font-mono">{{ $r->vehicle?->plate }}</span></td>
                                <td class="px-4 py-2">{{ $r->driver?->full_name ?? '—' }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ number_format($r->liters, 2, ',', ' ') }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ number_format($r->price_total, 2, ',', ' ') }}</td>
                                <td class="px-4 py-2 text-right text-zinc-500">{{ $r->price_per_liter ? number_format($r->price_per_liter, 2, ',', ' ') : '—' }}</td>
                                <td class="px-4 py-2 text-right">{{ $r->mileage_km ? number_format($r->mileage_km, 0, ',', ' ') . ' km' : '—' }}</td>
                                <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $r->station ?? '—' }}</td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('tankovani.edit', $r) }}" class="p-1.5 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded"><i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i></a>
                                    <form method="POST" action="{{ route('tankovani.destroy', $r) }}" class="inline" onsubmit="return confirm('Smazat tankování?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 inline-block hover:bg-red-500/10 rounded"><i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($refuelings->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">{{ $refuelings->links() }}</div>
            @endif
        @endif
    </div>
@endsection
