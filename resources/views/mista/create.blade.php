@extends('layouts.app', ['title' => 'Nové místo'])
@section('header', 'Nové místo')
@section('content')
    <form method="POST" action="{{ route('mista.store') }}">
        @csrf
        @include('mista._form', ['location' => null, 'types' => $types])
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('mista.index') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <x-btn type="submit" icon="check">Uložit</x-btn>
        </div>
    </form>
@endsection
