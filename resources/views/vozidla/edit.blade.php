@extends('layouts.app', ['title' => 'Upravit vozidlo'])
@section('header', 'Upravit: ' . $vehicle->name . ' (' . $vehicle->plate . ')')

@section('content')
    <form method="POST" action="{{ route('vozidla.update', $vehicle) }}" class="max-w-2xl">
        @csrf @method('PUT')
        @include('vozidla._form', ['vehicle' => $vehicle, 'drivers' => $drivers, 'devices' => $devices])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('vozidla.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Zpět
            </a>
            <x-btn type="submit" icon="check">Uložit změny</x-btn>
        </div>
    </form>

    {{-- Smazat — samostatný form mimo edit form (nested forms HTML zakazuje) --}}
    <form method="POST" action="{{ route('vozidla.destroy', $vehicle) }}"
          onsubmit="return confirm('Opravdu smazat vozidlo {{ $vehicle->name }} ({{ $vehicle->plate }})?\n\nTato akce je nevratná.');"
          class="max-w-2xl mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-800">
        @csrf @method('DELETE')
        <div class="flex items-center justify-between">
            <div class="text-xs text-zinc-500">
                <div class="font-semibold text-red-500 mb-0.5">Nebezpečná zóna</div>
                Smazání vozidla je nevratné. Jízdy a pozice zůstanou (FK SET NULL).
            </div>
            <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat vozidlo</x-btn>
        </div>
    </form>
@endsection
