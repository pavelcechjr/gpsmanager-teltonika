<!DOCTYPE html>
<html lang="cs" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení — gpsmanager</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter','ui-sans-serif','system-ui','sans-serif'] } } } };</script>
</head>
<body class="bg-zinc-950 text-zinc-100 font-sans antialiased min-h-screen flex items-center justify-center px-4">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-2xl mb-4 shadow-xl shadow-indigo-600/30">
            <i data-lucide="map-pin" class="w-8 h-8 text-white"></i>
        </div>
        <h1 class="text-2xl font-semibold tracking-tight">gpsmanager</h1>
        <p class="text-zinc-400 text-sm mt-1">Your Fleet monitoring</p>
    </div>

    <div class="bg-zinc-900/60 backdrop-blur border border-zinc-800 rounded-2xl p-6 space-y-4 shadow-2xl">
        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Microsoft 365 SSO — primární --}}
        <a href="{{ route('auth.microsoft') }}"
           class="flex items-center justify-center gap-3 w-full bg-white hover:bg-zinc-100 text-zinc-900 font-medium rounded-lg px-4 py-2.5 transition-colors shadow-sm">
            <svg width="18" height="18" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            <span>Přihlásit přes Microsoft 365</span>
        </a>

        <div class="flex items-center gap-3 text-xs text-zinc-500">
            <div class="flex-1 h-px bg-zinc-800"></div>
            <span>nebo</span>
            <div class="flex-1 h-px bg-zinc-800"></div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-300 mb-1.5">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                       class="w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2.5 text-sm placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                       placeholder="vase@example.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-zinc-300 mb-1.5">Heslo</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2.5 text-sm placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                       placeholder="••••••••">
            </div>

            <label class="flex items-center gap-2 text-sm text-zinc-400 select-none cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-zinc-700 bg-zinc-950 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-zinc-900">
                <span>Pamatovat přihlášení</span>
            </label>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-medium rounded-lg px-4 py-2.5 transition-colors shadow-lg shadow-indigo-600/30">
                Přihlásit emailem
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-zinc-600 mt-6">
        © {{ date('Y') }} Acme Fleet — vnitřní systém
    </p>
</div>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
