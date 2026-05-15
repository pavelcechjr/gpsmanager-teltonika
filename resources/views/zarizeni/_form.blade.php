@php $d = $device ?? null; @endphp
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
    <x-input label="IMEI"
             name="imei"
             :value="$d?->imei"
             placeholder="000000000000001"
             help="15 číslic z Teltonika jednotky. Najdete na štítku nebo přes konfigurátor."
             required
             inputmode="numeric"
             maxlength="15" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="SIM telefon"
                 name="phone_number"
                 :value="$d?->phone_number"
                 placeholder="+420 ..."
                 help="Tel. číslo SIM karty v jednotce (pro SMS konfiguraci)." />
        <x-input label="Model"
                 name="model"
                 :value="$d?->model"
                 placeholder="FMB920 / FMC650 / ..."
                 help="Teltonika model — určuje IO mapping a podporu protokolu." />
    </div>

    <div class="pt-2">
        <x-checkbox label="Aktivní (přijímat data z této jednotky, ukázat v seznamu vozidel)"
                    name="active" :checked="$d?->active ?? true" />
    </div>

    @if ($d?->last_seen_at)
        <div class="text-xs text-zinc-500 pt-2 border-t border-zinc-200 dark:border-zinc-800">
            <i data-lucide="clock" class="inline w-3 h-3 -mt-0.5"></i>
            Naposledy slyšeno: {{ $d->last_seen_at->diffForHumans() }} ({{ $d->last_seen_at->format('d.m.Y H:i:s') }})
        </div>
    @endif
</div>
