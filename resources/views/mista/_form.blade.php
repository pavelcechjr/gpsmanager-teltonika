@php
    $l = $location ?? null;
    $defaultLat = $l?->latitude ?? 49.1951;   // Brno
    $defaultLng = $l?->longitude ?? 16.6068;
    $defaultRadius = $l?->radius_meters ?? 200;
    $defaultColor = $l?->color ?? '#6366f1';
@endphp
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-4">
        <x-input label="Název" name="name" :value="$l?->name" required placeholder="Garáž Maloměřice" />
        <x-select label="Typ" name="type" :value="$l?->type" :options="$types" required empty="— vyber typ —" />
        <div class="grid grid-cols-2 gap-3">
            <x-input label="Šířka (lat)" name="latitude" :value="$l?->latitude ?? $defaultLat" required id="lat-input" step="any" />
            <x-input label="Délka (lng)" name="longitude" :value="$l?->longitude ?? $defaultLng" required id="lng-input" step="any" />
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                Poloměr geofence: <span id="radius-display" class="font-mono">{{ $defaultRadius }}</span> m
            </label>
            <input type="range" name="radius_meters" id="radius-input" min="10" max="5000" step="10" value="{{ $defaultRadius }}" class="w-full">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Barva</label>
                <input type="color" name="color" id="color-input" value="{{ $defaultColor }}" class="w-full h-9 rounded-lg cursor-pointer">
            </div>
            <div class="flex items-end">
                <x-checkbox label="Aktivní" name="active" :checked="$l?->active ?? true" />
            </div>
        </div>
        <x-textarea label="Poznámka" name="note" :value="$l?->note" rows="2" />
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
        <div id="loc-map" class="w-full" style="height:400px"></div>
        <div class="px-4 py-2 text-xs text-zinc-500 border-t border-zinc-200 dark:border-zinc-800">
            Klikni na mapě pro nastavení středu. Marker lze tažením přesouvat.
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const radiusInput = document.getElementById('radius-input');
    const radiusDisplay = document.getElementById('radius-display');
    const colorInput = document.getElementById('color-input');

    let lat = parseFloat(latInput.value);
    let lng = parseFloat(lngInput.value);
    let radius = parseInt(radiusInput.value);
    let color = colorInput.value;

    const map = L.map('loc-map').setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);

    let marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    let circle = L.circle([lat, lng], { radius: radius, color: color, fillColor: color, fillOpacity: 0.2 }).addTo(map);

    function syncFromMap(latLng) {
        latInput.value = latLng.lat.toFixed(7);
        lngInput.value = latLng.lng.toFixed(7);
        marker.setLatLng(latLng);
        circle.setLatLng(latLng);
    }

    map.on('click', (e) => syncFromMap(e.latlng));
    marker.on('dragend', (e) => syncFromMap(marker.getLatLng()));

    radiusInput.addEventListener('input', (e) => {
        radius = parseInt(e.target.value);
        radiusDisplay.textContent = radius;
        circle.setRadius(radius);
    });

    colorInput.addEventListener('input', (e) => {
        color = e.target.value;
        circle.setStyle({ color: color, fillColor: color });
    });

    // Update map when lat/lng inputs change manually
    [latInput, lngInput].forEach(inp => inp.addEventListener('change', () => {
        const l = parseFloat(latInput.value), n = parseFloat(lngInput.value);
        if (!isNaN(l) && !isNaN(n)) {
            map.setView([l, n], map.getZoom());
            marker.setLatLng([l, n]);
            circle.setLatLng([l, n]);
        }
    }));
})();
</script>
