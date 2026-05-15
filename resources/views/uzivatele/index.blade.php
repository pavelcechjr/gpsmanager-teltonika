@extends('layouts.app', ['title' => 'Uživatelé'])
@section('header', 'Uživatelé')

@section('header-actions')
    <x-btn :href="route('uzivatele.create')" icon="plus">Přidat uživatele</x-btn>
@endsection

@section('content')
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        @if ($users->isEmpty())
            <x-empty-state icon="users" title="Žádní uživatelé" />
        @else
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium">Jméno</th>
                        <th class="text-left px-4 py-2 font-medium">Email</th>
                        <th class="text-left px-4 py-2 font-medium">Role</th>
                        <th class="text-left px-4 py-2 font-medium">Vytvořeno</th>
                        <th class="text-right px-4 py-2 font-medium">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($users as $u)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-2 font-medium">{{ $u->name }} @if ($u->id === auth()->id())<span class="text-xs text-zinc-500">(ty)</span>@endif</td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $u->email }}</td>
                            <td class="px-4 py-2">
                                @switch($u->role)
                                    @case('admin')   <x-badge variant="indigo">{{ $u->role_label }}</x-badge> @break
                                    @case('manager') <x-badge variant="blue">{{ $u->role_label }}</x-badge> @break
                                    @case('driver')  <x-badge variant="amber">{{ $u->role_label }}</x-badge> @break
                                    @default        <x-badge variant="gray">{{ $u->role_label }}</x-badge>
                                @endswitch
                            </td>
                            <td class="px-4 py-2 text-zinc-500 text-xs">{{ $u->created_at?->format('d.m.Y') }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('uzivatele.edit', $u) }}" class="p-1.5 inline-block hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded"><i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i></a>
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('uzivatele.destroy', $u) }}" class="inline" onsubmit="return confirm('Smazat uživatele {{ $u->name }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 inline-block hover:bg-red-500/10 rounded"><i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($users->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800">{{ $users->links() }}</div>
            @endif
        @endif
    </div>
@endsection
