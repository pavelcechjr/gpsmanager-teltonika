@extends('layouts.app', ['title' => $rule ? 'Upravit pravidlo' : 'Nové pravidlo'])
@section('header', $rule ? "Pravidlo: {$rule->name}" : 'Nové pravidlo alarmu')

@section('content')
    @php
        $cfg = $rule?->config ?? [];
        $action = $rule ? route('alarmy.pravidla.update', $rule) : route('alarmy.pravidla.store');
        $emails = $rule?->notify_emails ? implode(', ', $rule->notify_emails) : '';
    @endphp
    <form method="POST" action="{{ $action }}" class="max-w-3xl" x-data="{ type: '{{ $rule?->type ?? '' }}' }">
        @csrf @if($rule) @method('PUT') @endif

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Název pravidla" name="name" :value="$rule?->name" placeholder="Překročení 130 km/h" required />
                <x-select label="Typ" name="type" :value="$rule?->type" :options="$types" required empty="— vyber typ —" x-model="type" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select label="Vozidlo" name="vehicle_id" :value="$rule?->vehicle_id" :options="$vehicles" empty="— všechna aktivní —" help="Necháš prázdné = aplikuje na všechna." />
                <x-select label="Závažnost" name="severity" :value="$rule?->severity ?? 'warn'" :options="$severities" required />
            </div>

            {{-- Type-specific config — shows depending on selected type --}}
            <div x-show="type === 'speed_limit'" x-cloak>
                <x-input label="Limit rychlosti (km/h)" type="number" name="config_limit_kmh" :value="$cfg['limit_kmh'] ?? 130" />
            </div>
            <div x-show="type === 'voltage_low'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Minimální napětí (V)" type="number" step="0.1" name="config_min_volt" :value="$cfg['min_volt'] ?? 12.0" />
                <x-input label="Trvání (min)" type="number" name="config_duration_min" :value="$cfg['duration_min'] ?? 5" help="Musí být pod limitem po tuto dobu." />
            </div>
            <div x-show="type === 'fuel_low'" x-cloak>
                <x-input label="Práh (%)" type="number" name="config_percent" :value="$cfg['percent'] ?? 15" />
            </div>
            <div x-show="type === 'parking_long'" x-cloak>
                <x-input label="Hodiny" type="number" name="config_hours" :value="$cfg['hours'] ?? 24" help="Stání bez ignition." />
            </div>
            <div x-show="type === 'device_offline'" x-cloak>
                <x-input label="Práh offline (min)" type="number" name="config_threshold_min" :value="$cfg['threshold_min'] ?? 30" />
            </div>
            <div x-show="type === 'night_movement'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Začátek noční doby" type="time" name="config_start_time" :value="$cfg['start_time'] ?? '22:00'" />
                <x-input label="Konec noční doby" type="time" name="config_end_time" :value="$cfg['end_time'] ?? '05:00'" />
            </div>
            <div x-show="type === 'hv_battery_low'" x-cloak>
                <x-input label="Práh SOC (%)" type="number" name="config_percent" :value="$cfg['percent'] ?? 15" help="Vyžaduje custom OBD PID v Teltonika Configurator." />
            </div>
            <div x-show="type === 'dtc_present'" x-cloak class="text-sm text-zinc-500">Bez další konfigurace — spustí se při jakémkoli DTC code z OBD.</div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Cooldown (min)" type="number" name="cooldown_min" :value="$rule?->cooldown_min ?? 15" required help="Po triggernutí ne triggernu znova pro stejné pravidlo+vozidlo po tuto dobu." />
                <x-input label="Notifikace (emaily, oddělené čárkou)" name="notify_emails_csv" :value="$emails" placeholder="admin@example.com, manager@example.com" />
            </div>

            <x-textarea label="Poznámka" name="note" :value="$rule?->note" rows="2" />
            <x-checkbox label="Aktivní" name="active" :checked="$rule?->active ?? true" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('alarmy.pravidla') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <div class="flex gap-2">
                @if ($rule)
                    <form method="POST" action="{{ route('alarmy.pravidla.destroy', $rule) }}" onsubmit="return confirm('Smazat?');">
                        @csrf @method('DELETE')
                        <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat</x-btn>
                    </form>
                @endif
                <x-btn type="submit" icon="check">Uložit</x-btn>
            </div>
        </div>
    </form>
@endsection
