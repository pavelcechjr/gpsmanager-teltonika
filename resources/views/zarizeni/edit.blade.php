@extends('layouts.app', ['title' => 'Upravit zařízení'])
@section('header', 'Upravit: ' . $device->imei)

@section('content')
    <form method="POST" action="{{ route('zarizeni.update', $device) }}" class="max-w-2xl">
        @csrf @method('PUT')
        @include('zarizeni._form', ['device' => $device])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('zarizeni.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Zpět
            </a>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('zarizeni.destroy', $device) }}"
                      onsubmit="return confirm('Opravdu smazat zařízení {{ $device->imei }}?');">
                    @csrf @method('DELETE')
                    <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat</x-btn>
                </form>
                <x-btn type="submit" icon="check">Uložit změny</x-btn>
            </div>
        </div>
    </form>
@endsection
