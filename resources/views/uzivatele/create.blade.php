@extends('layouts.app', ['title' => 'Nový uživatel'])
@section('header', 'Nový uživatel')
@section('content')
    <form method="POST" action="{{ route('uzivatele.store') }}" class="max-w-2xl">
        @csrf
        @include('uzivatele._form', ['user' => null, 'roles' => $roles])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('uzivatele.index') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <x-btn type="submit" icon="check">Vytvořit</x-btn>
        </div>
    </form>
@endsection
