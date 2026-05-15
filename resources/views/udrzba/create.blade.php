@extends('layouts.app', ['title' => 'Nový úkon'])
@section('header', 'Nový úkon údržby')
@section('content')
    <form method="POST" action="{{ route('udrzba.store') }}" class="max-w-3xl">
        @csrf
        @include('udrzba._form', ['maintenance' => null, 'vehicles' => $vehicles, 'types' => $types])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('udrzba.index') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <x-btn type="submit" icon="check">Uložit</x-btn>
        </div>
    </form>
@endsection
