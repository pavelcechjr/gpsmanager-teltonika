@extends('layouts.app', ['title' => 'Nové tankování'])
@section('header', 'Nové tankování')
@section('content')
    <form method="POST" action="{{ route('tankovani.store') }}" class="max-w-3xl">
        @csrf
        @include('tankovani._form', ['refueling' => null, 'vehicles' => $vehicles, 'drivers' => $drivers])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('tankovani.index') }}" class="text-sm text-zinc-500 hover:text-zinc-300"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <x-btn type="submit" icon="check">Uložit</x-btn>
        </div>
    </form>
@endsection
