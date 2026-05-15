@extends('layouts.app', ['title' => $group ? 'Upravit skupinu' : 'Nová skupina'])
@section('header', $group ? "Skupina: {$group->name}" : 'Nová skupina vozidel')

@section('content')
    <form method="POST" action="{{ $group ? route('vozidla.skupiny.update', $group) : route('vozidla.skupiny.store') }}" class="max-w-3xl">
        @csrf @if($group) @method('PUT') @endif

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2"><x-input label="Název" name="name" :value="$group?->name" required /></div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Barva</label>
                    <input type="color" name="color" value="{{ $group?->color ?? '#6366f1' }}" class="w-full h-9 rounded-lg cursor-pointer">
                </div>
            </div>
            <x-textarea label="Popis" name="description" :value="$group?->description" />

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Vozidla v této skupině</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto p-3 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg">
                    @forelse ($vehicles as $v)
                        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white dark:hover:bg-zinc-800 p-1.5 rounded">
                            <input type="checkbox" name="vehicles[]" value="{{ $v->id }}" @checked(in_array($v->id, $selected)) class="rounded">
                            <span class="truncate">{{ $v->name }} <span class="text-zinc-500 text-xs font-mono">{{ $v->plate }}</span></span>
                        </label>
                    @empty
                        <div class="text-sm text-zinc-500 col-span-2">Žádná aktivní vozidla.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('vozidla.skupiny') }}" class="text-sm text-zinc-500"><i data-lucide="arrow-left" class="inline w-4 h-4"></i> Zpět</a>
            <div class="flex gap-2">
                @if ($group)
                    <form method="POST" action="{{ route('vozidla.skupiny.destroy', $group) }}" onsubmit="return confirm('Smazat skupinu?');">
                        @csrf @method('DELETE')
                        <x-btn type="submit" variant="ghost" icon="trash-2" class="!text-red-500">Smazat</x-btn>
                    </form>
                @endif
                <x-btn type="submit" icon="check">Uložit</x-btn>
            </div>
        </div>
    </form>
@endsection
