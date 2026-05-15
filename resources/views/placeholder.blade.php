@extends('layouts.app', ['title' => $title])
@section('header', $title)

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-12 text-center max-w-2xl mx-auto">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-zinc-100 dark:bg-zinc-800 mb-4">
            <i data-lucide="hammer" class="w-7 h-7 text-zinc-500"></i>
        </div>
        <h2 class="text-lg font-semibold mb-2">Sekce „{{ $title }}" se staví</h2>
        @isset($desc)
            <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-md mx-auto leading-relaxed">{{ $desc }}</p>
        @else
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Bude k dispozici v dalším buildu.</p>
        @endisset
    </div>
@endsection
