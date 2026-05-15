@extends('layouts.app', ['title' => 'Upravit místo'])
@section('header', 'Upravit: ' . $location->name)
@section('content')
    <form method="POST" action="{{ route('mista.update', $location) }}">
        @csrf @method('PUT')
        @include('mista._form', ['location' => $location, 'types' => $types])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('mista.index') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('mista.destroy', $location) }}" onsubmit="return confirm('Smazat?');">
                    @csrf @method('DELETE')
                    <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat</x-btn>
                </form>
                <x-btn type="submit" icon="check">Uložit změny</x-btn>
            </div>
        </div>
    </form>
@endsection
