@extends('layouts.app', ['title' => 'Nové vozidlo'])
@section('header', 'Nové vozidlo')

@section('content')
    <form method="POST" action="{{ route('vozidla.store') }}" class="max-w-2xl">
        @csrf
        @include('vozidla._form', ['vehicle' => null, 'drivers' => $drivers, 'devices' => $devices])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('vozidla.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Zpět
            </a>
            <x-btn type="submit" icon="check">Vytvořit</x-btn>
        </div>
    </form>
@endsection
