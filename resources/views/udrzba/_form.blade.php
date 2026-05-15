@php $m = $maintenance ?? null; @endphp
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-select label="Vozidlo" name="vehicle_id" :value="$m?->vehicle_id" :options="$vehicles" required empty="— vyber —" />
        <x-select label="Typ úkonu" name="type" :value="$m?->type" :options="$types" required empty="— vyber typ —" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Plánovaný termín" type="date" name="planned_at" :value="$m?->planned_at?->format('Y-m-d')" />
        <x-input label="Provedeno" type="date" name="performed_at" :value="$m?->performed_at?->format('Y-m-d')" help="Vyplň po provedení." />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Stav tachometru (km)" type="number" name="mileage_km" :value="$m?->mileage_km" />
        <x-input label="Cena (Kč)" type="number" step="0.01" name="price" :value="$m?->price" />
    </div>
    <x-input label="Dodavatel / servis" name="supplier" :value="$m?->supplier" placeholder="Servis MAN Brno-Slatina" />
    <x-textarea label="Poznámka" name="note" :value="$m?->note" rows="3" />
</div>
