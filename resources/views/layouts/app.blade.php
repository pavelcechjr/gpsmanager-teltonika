<!DOCTYPE html>
<html lang="cs" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'gpsmanager' }} — gpsmanager</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        nav: { 900: '#0f172a', 800: '#1a2541', 700: '#243152', 600: '#2d3c63' },
                    },
                },
            },
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.2); }
        /* Leaflet ↔ Tailwind preflight fix */
        .leaflet-container img.leaflet-tile,
        .leaflet-container img.leaflet-marker-icon,
        .leaflet-container img.leaflet-image-layer { max-width: none !important; }
        .leaflet-control-attribution,
        .leaflet-control-attribution * { box-sizing: content-box; }
        .leaflet-pane img { max-width: none !important; }
    </style>
</head>
<body class="bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 font-sans antialiased">
@php
    $userName = auth()->user()->name ?? '';
    $userParts = preg_split('/\s+/', trim($userName)) ?: [''];
    $userInitials = strtoupper(mb_substr($userParts[0] ?? '', 0, 1) . (count($userParts) > 1 ? mb_substr(end($userParts), 0, 1) : ''));

    $nav = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
        ['route' => 'kniha-jizd.index', 'match' => 'kniha-jizd', 'label' => 'Kniha jízd', 'icon' => 'route', 'children' => [
            ['route' => 'kniha-jizd.index', 'match' => 'kniha-jizd', 'label' => 'Přehled jízd'],
            ['route' => 'kniha-jizd.statistiky',  'label' => 'Statistiky'],
            ['route' => 'kniha-jizd.export',      'label' => 'Export'],
        ]],
        ['route' => 'tankovani.index', 'match' => 'tankovani', 'label' => 'Tankování', 'icon' => 'fuel'],
        ['route' => 'udrzba.index',    'match' => 'udrzba',    'label' => 'Údržba',    'icon' => 'wrench'],
        ['route' => 'vozidla.index', 'match' => 'vozidla', 'label' => 'Vozidla', 'icon' => 'truck', 'children' => [
            ['route' => 'vozidla.index',   'match' => 'vozidla', 'label' => 'Přehled'],
            ['route' => 'vozidla.skupiny', 'label' => 'Skupiny'],
        ]],
        ['route' => 'zarizeni.index',  'match' => 'zarizeni', 'label' => 'Zařízení',  'icon' => 'cpu',   'children' => [
            ['route' => 'zarizeni.index',   'match' => 'zarizeni', 'label' => 'Přehled'],
            ['route' => 'zarizeni.skupiny', 'label' => 'Skupiny'],
            ['route' => 'zarizeni.typy',    'label' => 'Typy'],
        ]],
        ['route' => 'alarmy', 'match' => 'alarmy', 'label' => 'Alarmy', 'icon' => 'bell', 'children' => [
            ['route' => 'alarmy',           'label' => 'Aktivní'],
            ['route' => 'alarmy.historie',  'label' => 'Historie'],
            ['route' => 'alarmy.pravidla',  'match' => 'alarmy.pravidla', 'label' => 'Pravidla'],
        ]],
        ['route' => 'mista.index', 'match' => 'mista', 'label' => 'Místa',   'icon' => 'map-pin'],
        ['route' => 'monitor', 'label' => 'Monitor', 'icon' => 'monitor'],
        ['route' => 'uzivatele.index', 'match' => 'uzivatele', 'label' => 'Uživatelé', 'icon' => 'users', 'children' => [
            ['route' => 'uzivatele.index', 'match' => 'uzivatele', 'label' => 'Přehled'],
            ['route' => 'ridici.index', 'match' => 'ridici', 'label' => 'Řidiči'],
        ]],
        ['route' => 'servery',     'label' => 'Servery',      'icon' => 'server'],
        ['route' => 'konfigurace', 'label' => 'Konfigurace',  'icon' => 'settings'],
        ['route' => 'casove-zony', 'label' => 'Časové zóny',  'icon' => 'globe'],
    ];

    $routeName = request()->route()?->getName() ?? '';
    $matchPrefix = fn ($child) => $child['match'] ?? $child['route'];
    $isChildActive = function ($child) use ($routeName, $matchPrefix) {
        $m = $matchPrefix($child);
        return $routeName === $child['route'] || $routeName === $m || str_starts_with($routeName, $m . '.');
    };

    $expandedKey = null;
    foreach ($nav as $i => $item) {
        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($isChildActive($child)) {
                    $expandedKey = $i;
                    break 2;
                }
            }
        }
    }
@endphp
<div class="flex h-screen overflow-hidden">
    <aside class="w-64 shrink-0 bg-nav-800 text-slate-200 flex flex-col" x-data="{ open: @json($expandedKey) }">
        <div class="px-6 py-5 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-600/30">
                    <i data-lucide="radar" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <div class="font-semibold tracking-tight leading-tight text-white">gpsmanager</div>
                    <div class="text-xs text-slate-400">Your Fleet</div>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto scrollbar-thin">
            @foreach ($nav as $i => $item)
                @php
                    $hasChildren = !empty($item['children']);
                    $itemActive = $routeName === $item['route']
                        || ($item['route'] && str_starts_with($routeName, $item['route'] . '.'))
                        || ($hasChildren && collect($item['children'])->contains(fn ($c) => $isChildActive($c)));
                @endphp

                @if ($hasChildren)
                    <div>
                        <button type="button"
                                @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                                       {{ $itemActive ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 {{ $itemActive ? 'text-white' : 'text-slate-400' }}"></i>
                            <span class="flex-1 text-left">{{ $item['label'] }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open === {{ $i }}" x-cloak x-transition.duration.200ms class="mt-0.5 ml-9 space-y-0.5 border-l border-white/10 pl-3">
                            @foreach ($item['children'] as $child)
                                @php $childActive = $isChildActive($child); @endphp
                                <a href="{{ Route::has($child['route']) ? route($child['route']) : '#' }}"
                                   class="block px-3 py-1.5 rounded-md text-sm transition-colors
                                          {{ $childActive ? 'text-white font-medium bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ $itemActive ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 {{ $itemActive ? 'text-white' : 'text-slate-400' }}"></i>
                        <span class="flex-1 text-left">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="px-3 py-3 border-t border-white/5">
            <a href="{{ route('profil') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors
                      {{ request()->routeIs('profil') ? 'bg-white/10' : 'hover:bg-white/5' }}">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-semibold text-white">
                    {{ $userInitials ?: '?' }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate text-white">{{ $userName }}</div>
                    <div class="text-xs text-slate-400 truncate">{{ auth()->user()->email ?? '' }}</div>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Odhlásit</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-zinc-50 dark:bg-zinc-950">
        <header class="sticky top-0 z-10 bg-white/80 dark:bg-zinc-900/80 backdrop-blur border-b border-zinc-200 dark:border-zinc-800 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-lg font-semibold tracking-tight">@yield('header', $title ?? '')</h1>
                @hasSection('header-actions')
                    <div class="flex items-center gap-2">@yield('header-actions')</div>
                @endif
            </div>
        </header>
        <div class="p-6">
            @if (session('status'))
                <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
    function renderIcons() { if (window.lucide) lucide.createIcons(); }
    renderIcons();
    document.addEventListener('alpine:initialized', renderIcons);
    document.addEventListener('DOMContentLoaded', renderIcons);
</script>
</body>
</html>
