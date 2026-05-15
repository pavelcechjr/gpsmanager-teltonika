@extends('layouts.app', ['title' => 'Upravit úkon'])
@section('header', 'Upravit úkon údržby')
@section('content')
    <form method="POST" action="{{ route('udrzba.update', $maintenance) }}" class="max-w-3xl">
        @csrf @method('PUT')
        @include('udrzba._form', ['maintenance' => $maintenance, 'vehicles' => $vehicles, 'types' => $types])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('udrzba.index') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <x-btn type="submit" icon="check">Uložit změny</x-btn>
        </div>
    </form>
@endsection
