@extends('layouts.app', ['title' => 'Nové zařízení'])
@section('header', 'Nové zařízení')

@section('content')
    <form method="POST" action="{{ route('zarizeni.store') }}" class="max-w-2xl">
        @csrf
        @include('zarizeni._form', ['device' => null])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('zarizeni.index') }}" class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Zpět
            </a>
            <x-btn type="submit" icon="check">Vytvořit</x-btn>
        </div>
    </form>
@endsection
