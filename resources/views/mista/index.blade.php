@extends('layouts.app', ['title' => 'Místa'])
@section('header', 'Místa a geofence')

@section('header-actions')
    <x-btn :href="route('mista.create')" icon="plus">Přidat místo</x-btn>
@endsection

@section('content')
    @if ($locations->isEmpty())
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl">
            <x-empty-state icon="map-pin" title="Žádná místa" description="Definuj garáže, klienty, čerpací stanice nebo libovolné body zájmu s geofence.">
                <x-btn :href="route('mista.create')" icon="plus">Přidat místo</x-btn>
            </x-empty-state>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                <div id="all-map" class="w-full" style="height: 500px;"></div>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <h3 class="text-sm font-semibold">Seznam míst ({{ $locations->count() }})</h3>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800 max-h-[450px] overflow-y-auto">
                    @foreach ($locations as $loc)
                        <div class="px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <div class="flex items-start justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $loc->color }}"></span>
                                        <div class="font-medium truncate">{{ $loc->name }}</div>
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-0.5">{{ $loc->type_label }} · {{ $loc->radius_meters }} m</div>
                                    <div class="text-xs text-zinc-500 font-mono">{{ number_format($loc->latitude, 5, '.', '') }}, {{ number_format($loc->longitude, 5, '.', '') }}</div>
                                </div>
                                <a href="{{ route('mista.edit', $loc) }}" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded">
                                    <i data-lucide="pencil" class="w-4 h-4 text-zinc-500"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @php $locJson = json_encode($locations->map(fn ($l) => [
            'id' => $l->id, 'name' => $l->name, 'type' => $l->type_label,
            'lat' => (float) $l->latitude, 'lng' => (float) $l->longitude,
            'radius' => $l->radius_meters, 'color' => $l->color,
            'url' => route('mista.edit', $l),
        ])); @endphp

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
        (function () {
            const locs = {!! $locJson !!};
            const map = L.map('all-map').setView([49.1951, 16.6068], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(map);
            const group = L.featureGroup().addTo(map);
            locs.forEach(l => {
                L.circle([l.lat, l.lng], { radius: l.radius, color: l.color, fillColor: l.color, fillOpacity: 0.2 }).addTo(group)
                  .bindPopup(`<b>${l.name}</b><br>${l.type}<br><a href="${l.url}">Upravit</a>`);
                L.marker([l.lat, l.lng]).addTo(group);
            });
            if (locs.length) map.fitBounds(group.getBounds(), { padding: [40, 40] });
        })();
        </script>
    @endif
@endsection
