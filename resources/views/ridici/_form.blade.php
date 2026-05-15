@php $d = $driver ?? null; @endphp
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Jméno"    name="first_name" :value="$d?->first_name" required />
        <x-input label="Příjmení" name="last_name"  :value="$d?->last_name"  required />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Email"   type="email" name="email" :value="$d?->email" placeholder="ridic@example.com" />
        <x-input label="Telefon" name="phone" :value="$d?->phone" placeholder="+420 ..." />
    </div>
    <x-textarea label="Poznámka" name="note" :value="$d?->note" rows="3" />
    <div class="pt-2">
        <x-checkbox label="Aktivní (zobrazit v seznamech a nabídnout pro přiřazení k vozidlu)"
                    name="active" :checked="$d?->active ?? true" />
    </div>
</div>
