@extends('layouts.app', ['title' => 'Upravit uživatele'])
@section('header', 'Upravit: ' . $user->name)
@section('content')
    <form method="POST" action="{{ route('uzivatele.update', $user) }}" class="max-w-2xl">
        @csrf @method('PUT')
        @include('uzivatele._form', ['user' => $user, 'roles' => $roles])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('uzivatele.index') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <div class="flex gap-2">
                @if ($user->id !== auth()->id())
                    <form method="POST" action="{{ route('uzivatele.destroy', $user) }}" onsubmit="return confirm('Smazat?');">
                        @csrf @method('DELETE')
                        <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat</x-btn>
                    </form>
                @endif
                <x-btn type="submit" icon="check">Uložit změny</x-btn>
            </div>
        </div>
    </form>
@endsection
