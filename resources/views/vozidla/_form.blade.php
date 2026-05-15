@php $v = $vehicle ?? null; @endphp
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Název"
                 name="name"
                 :value="$v?->name"
                 placeholder="Dacia Duster"
                 help="Interní jméno (např. model auta)."
                 required />
        <x-input label="SPZ"
                 name="plate"
                 :value="$v?->plate"
                 placeholder="6A2 1234"
                 help="Registrační značka — bude normalizována na velká písmena."
                 required />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-select label="Značka"
                  name="brand"
                  :value="$v?->brand"
                  :options="\App\Models\Vehicle::BRANDS"
                  empty="— neuvedeno —"
                  help="Loga: Volkswagen, Ford, Škoda, Audi… (zobrazí se na živé mapě)." />
        <x-select label="Pohon"
                  name="fuel_type"
                  :value="$v?->fuel_type"
                  :options="\App\Models\Vehicle::FUEL_TYPES"
                  empty="— neuvedeno —"
                  help="Hybrid / PHEV / EV dostane zelený eco badge v Knize jízd." />
        <x-input label="Barva" name="color" :value="$v?->color" placeholder="modrá" />
    </div>

    <x-select label="Default řidič"
              name="default_driver_id"
              :value="$v?->default_driver_id"
              :options="$drivers"
              empty="— bez default řidiče —"
              help="Bude předvyplněn u nových jízd, lze přepsat v Knize jízd." />

    <x-select label="Teltonika jednotka"
              name="device_id"
              :value="$v?->device_id"
              :options="$devices"
              empty="— bez jednotky —"
              help="Vozidlo bez jednotky nebude posílat polohu. Jedna jednotka = jedno vozidlo (1:1)." />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-input label="Stav tachometru (km)"
                 type="number"
                 name="odometer_km"
                 :value="$v?->odometer_km"
                 placeholder="94000"
                 help="Aktuální stav z budíku auta." />
        <x-input label="Stav ze dne"
                 type="date"
                 name="odometer_updated_at"
                 :value="$v?->odometer_updated_at?->format('Y-m-d')"
                 help="Default = dnes." />
        <x-input label="Objem nádrže (l)"
                 type="number"
                 step="0.1"
                 name="fuel_tank_l"
                 :value="$v?->fuel_tank_l"
                 placeholder="45"
                 help="Pro výpočet spotřeby (Golf MK8 e-hybrid ~40 l)." />
    </div>

    <x-textarea label="Poznámka" name="note" :value="$v?->note" rows="3" />

    <div class="pt-2">
        <x-checkbox label="Aktivní (ukázat v seznamech a Knize jízd)"
                    name="active" :checked="$v?->active ?? true" />
    </div>
</div>
