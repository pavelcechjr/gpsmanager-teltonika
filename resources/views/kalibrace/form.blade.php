@extends('layouts.app', ['title' => $calibration ? 'Upravit kalibraci' : 'Kalibrace tachometru'])
@section('header', 'Kalibrace tachometru — ' . $vehicle->name)

@section('content')
    @php
        $action = $calibration
            ? route('vozidla.kalibrace.update', ['vozidla' => $vehicle, 'calibration' => $calibration])
            : route('vozidla.kalibrace.store', ['vozidla' => $vehicle]);
    @endphp
    <form method="POST" action="{{ $action }}" class="max-w-2xl">
        @csrf @if($calibration) @method('PUT') @endif

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
            <div class="bg-indigo-500/5 border border-indigo-500/20 rounded-lg p-3 text-sm">
                <div class="flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 mt-0.5 text-indigo-500 shrink-0"></i>
                    <div class="text-zinc-700 dark:text-zinc-300">
                        Kalibrace přidá / odečte km od stavu tachometru, který gpsmanager dopočítává z jízd.
                        Použij když reálný tachometr v autě nesedí se systémem (jezdilo se bez Teltoniky, ručně se přemístilo auto, atd.).
                        @if ($estimated !== null)
                            <br>Aktuální odhad systému: <strong>{{ number_format($estimated, 0, ',', ' ') }} km</strong>.
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Datum kalibrace"
                         type="datetime-local"
                         name="applied_at"
                         :value="$calibration?->applied_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')"
                         required
                         help="Chronologicky kdy ta korekce platí." />
                <x-input label="Korekce (km)"
                         type="number"
                         name="delta_km"
                         :value="$calibration?->delta_km"
                         placeholder="např. 35 nebo -12"
                         required
                         help="Plus / minus rozdíl. Záporné = sníží tachometr." />
            </div>

            <x-textarea label="Poznámka" name="note" :value="$calibration?->note" rows="3" placeholder="Jezdilo se 2 dny bez Teltoniky / přemístění z dílny / oprava cliku, atd." />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('vozidla.show', $vehicle) }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět na vozidlo</a>
            <div class="flex gap-2">
                @if ($calibration)
                    <form method="POST" action="{{ route('vozidla.kalibrace.destroy', ['vozidla' => $vehicle, 'calibration' => $calibration]) }}" onsubmit="return confirm('Smazat kalibraci?');">
                        @csrf @method('DELETE')
                        <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat</x-btn>
                    </form>
                @endif
                <x-btn type="submit" icon="check">Uložit</x-btn>
            </div>
        </div>
    </form>
@endsection
