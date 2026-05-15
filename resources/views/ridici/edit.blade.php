@extends('layouts.app', ['title' => 'Upravit řidiče'])
@section('header', 'Upravit: ' . $driver->full_name)

@section('content')
    <form method="POST" action="{{ route('ridici.update', $driver) }}" class="max-w-2xl">
        @csrf @method('PUT')
        @include('ridici._form', ['driver' => $driver])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('ridici.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Zpět
            </a>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('ridici.destroy', $driver) }}"
                      onsubmit="return confirm('Opravdu smazat řidiče {{ $driver->full_name }}?');">
                    @csrf @method('DELETE')
                    <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat</x-btn>
                </form>
                <x-btn type="submit" icon="check">Uložit změny</x-btn>
            </div>
        </div>
    </form>
@endsection
