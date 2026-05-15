@extends('layouts.app', ['title' => 'Upravit jízdu'])
@section('header', 'Upravit jízdu')

@section('content')
    <div class="max-w-2xl space-y-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Vozidlo</div>
                    <div class="font-medium">{{ $trip->vehicle?->name ?? '—' }} <span class="text-zinc-500 font-mono text-xs">{{ $trip->vehicle?->plate }}</span></div>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Doba</div>
                    @php
                        $d = $trip->duration_seconds ?: ($trip->ended_at ? $trip->ended_at->diffInSeconds($trip->started_at) : null);
                    @endphp
                    <div class="font-medium">
                        @if ($d)
                            {{ intdiv($d, 3600) }} h {{ str_pad((string) intdiv($d % 3600, 60), 2, '0', STR_PAD_LEFT) }} min
                        @else — @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Zahájení</div>
                    <div class="text-sm">{{ $trip->started_at?->format('d.m.Y H:i:s') ?? '—' }}</div>
                    <div class="text-xs text-zinc-500">{{ $trip->start_address ?? '' }}</div>
                </div>
                <div>
                    <div class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Konec</div>
                    <div class="text-sm">{{ $trip->ended_at?->format('d.m.Y H:i:s') ?? '— probíhá —' }}</div>
                    <div class="text-xs text-zinc-500">{{ $trip->end_address ?? '' }}</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('kniha-jizd.update', $trip) }}">
            @csrf @method('PUT')
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
                <x-select label="Řidič"
                          name="driver_id"
                          :value="$trip->driver_id"
                          :options="$drivers"
                          empty="— bez řidiče —"
                          help="Default je přiřazený default řidič vozidla. Můžeš ručně přepsat (např. když auto řídil někdo jiný)." />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Typ jízdy</label>
                        <div class="flex gap-2">
                            <input type="hidden" name="is_private" value="0">
                            <label class="flex-1 flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition-colors {{ !$trip->is_private ? 'border-indigo-500 bg-indigo-500/5' : 'border-zinc-300 dark:border-zinc-700' }}">
                                <input type="radio" name="is_private" value="0" @checked(!$trip->is_private) class="text-indigo-600">
                                <i data-lucide="briefcase" class="w-4 h-4 text-indigo-500"></i>
                                <span class="text-sm font-medium">Služební</span>
                            </label>
                            <label class="flex-1 flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition-colors {{ $trip->is_private ? 'border-amber-500 bg-amber-500/5' : 'border-zinc-300 dark:border-zinc-700' }}">
                                <input type="radio" name="is_private" value="1" @checked($trip->is_private) class="text-amber-600">
                                <i data-lucide="house" class="w-4 h-4 text-amber-500"></i>
                                <span class="text-sm font-medium">Soukromá</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">Default je služební. Soukromé jízdy se odečítají z daňového základu.</p>
                    </div>
                    @if ($trip->odometer_end_km)
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Stav tachometru na konci</label>
                            <div class="text-2xl font-semibold">{{ number_format($trip->odometer_end_km, 0, ',', ' ') }} <span class="text-sm text-zinc-500 font-normal">km</span></div>
                            <p class="mt-1 text-xs text-zinc-500">Vypočteno systémem na konci jízdy.</p>
                        </div>
                    @endif
                </div>

                <x-textarea label="Poznámka" name="note" :value="$trip->note" rows="4" />
            </div>
            <div class="flex items-center justify-between mt-6">
                <a href="{{ route('kniha-jizd.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Zpět
                </a>
                <x-btn type="submit" icon="check">Uložit změny</x-btn>
            </div>
        </form>
    </div>
@endsection
