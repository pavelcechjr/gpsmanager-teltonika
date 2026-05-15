@extends('layouts.app', ['title' => 'Profil'])
@section('header', 'Profil')

@section('content')
    @php $u = auth()->user(); @endphp

    @if ($errors->any() && session('status') === null)
        {{-- errors shown inline via @error in inputs --}}
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 max-w-5xl">
        {{-- Identity card --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
            <div class="flex flex-col items-center text-center">
                @php
                    $parts = preg_split('/\s+/', trim($u->name)) ?: [''];
                    $ini = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
                @endphp
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-2xl font-semibold text-white mb-3">
                    {{ $ini ?: '?' }}
                </div>
                <div class="font-semibold">{{ $u->name }}</div>
                <div class="text-sm text-zinc-500">{{ $u->email }}</div>
                <div class="text-xs text-zinc-500 mt-2">Účet vytvořen {{ $u->created_at?->format('d.m.Y') ?? '—' }}</div>
            </div>
        </div>

        {{-- Forms --}}
        <div class="lg:col-span-2 space-y-4">
            <form method="POST" action="{{ route('profil.update') }}">
                @csrf @method('PATCH')
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-1">Identita</h3>
                    <p class="text-xs text-zinc-500 mb-4">Jméno a kontaktní email.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Jméno" name="name" :value="$u->name" required />
                        <x-input label="Email" name="email" type="email" :value="$u->email" required />
                    </div>
                    <div class="flex justify-end mt-4">
                        <x-btn type="submit" icon="check">Uložit změny</x-btn>
                    </div>
                </div>
            </form>

            <form method="POST" action="{{ route('profil.password') }}">
                @csrf @method('PATCH')
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5">
                    <h3 class="text-sm font-semibold mb-1">Změna hesla</h3>
                    <p class="text-xs text-zinc-500 mb-4">Doporučujeme heslo o minimální délce 8 znaků.</p>
                    <div class="space-y-4">
                        <x-input label="Současné heslo" name="current_password" type="password" required autocomplete="current-password" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-input label="Nové heslo" name="password" type="password" required autocomplete="new-password" />
                            <x-input label="Potvrzení nového hesla" name="password_confirmation" type="password" required autocomplete="new-password" />
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <x-btn type="submit" variant="primary" icon="key-round">Změnit heslo</x-btn>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
