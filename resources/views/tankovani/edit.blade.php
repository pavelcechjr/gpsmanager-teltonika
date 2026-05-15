@extends('layouts.app', ['title' => 'Upravit tankování'])
@section('header', 'Upravit tankování')
@section('content')
    <form method="POST" action="{{ route('tankovani.update', $refueling) }}" class="max-w-3xl">
        @csrf @method('PUT')
        @include('tankovani._form', ['refueling' => $refueling, 'vehicles' => $vehicles, 'drivers' => $drivers])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('tankovani.index') }}" class="text-sm text-zinc-500 hover:text-zinc-300"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <x-btn type="submit" icon="check">Uložit změny</x-btn>
        </div>
    </form>
@endsection
