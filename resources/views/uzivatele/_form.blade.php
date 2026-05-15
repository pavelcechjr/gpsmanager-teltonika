@php $u = $user ?? null; $isNew = !$u; @endphp
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="Jméno" name="name" :value="$u?->name" required />
        <x-input label="Email" name="email" type="email" :value="$u?->email" required />
    </div>
    <x-select label="Role" name="role" :value="$u?->role ?? 'admin'" :options="$roles" required />
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-input label="{{ $isNew ? 'Heslo' : 'Nové heslo (nech prázdné pro zachování)' }}"
                 name="password" type="password" :required="$isNew" autocomplete="new-password" />
        <x-input label="Potvrzení hesla" name="password_confirmation" type="password" :required="$isNew" autocomplete="new-password" />
    </div>
</div>
