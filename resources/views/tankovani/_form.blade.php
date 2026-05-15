@php $r = $refueling ?? null; @endphp
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-select label="Vozidlo" name="vehicle_id" :value="$r?->vehicle_id" :options="$vehicles" required empty="— vyber vozidlo —" />
        <x-select label="Řidič" name="driver_id" :value="$r?->driver_id" :options="$drivers" empty="— nezáleží —" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-input label="Datum / čas tankování" type="datetime-local" name="fueled_at" :value="$r?->fueled_at?->format('Y-m-d\TH:i')" required />
        <x-input label="Kilometráž" type="number" name="mileage_km" :value="$r?->mileage_km" placeholder="125000" />
        <x-input label="Palivo" name="fuel_type" :value="$r?->fuel_type ?? 'Nafta'" required help="Nafta / Benzin / LPG / ..." />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Litry" type="number" step="0.01" name="liters" :value="$r?->liters" required placeholder="35.50" />
        <x-input label="Celková cena (Kč)" type="number" step="0.01" name="price_total" :value="$r?->price_total" required placeholder="1200.00" />
    </div>
    <x-input label="Stanice" name="station" :value="$r?->station" placeholder="OMV Modřice" />
    <x-textarea label="Poznámka" name="note" :value="$r?->note" rows="2" />
</div>
