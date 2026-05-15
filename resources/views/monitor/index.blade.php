@extends('layouts.app', ['title' => 'Monitor'])
@section('header', 'Live monitor')

@section('header-actions')
    <span class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        live update každé 2 s
    </span>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
            <div id="live-map" class="w-full" style="height: 600px;"></div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold">Vozidla</h3>
                <span id="vehicle-count" class="text-xs text-zinc-500">—</span>
            </div>
            <div id="vehicle-list" class="divide-y divide-zinc-200 dark:divide-zinc-800 max-h-[550px] overflow-y-auto">
                <div class="px-4 py-6 text-center text-sm text-zinc-500">Načítám…</div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        /* Smooth marker position interpolation — překryje skok mezi packety
           Leaflet aplikuje transform: translate3d() na .leaflet-marker-icon */
        .leaflet-marker-icon { transition: transform 1.8s linear !important; }
        /* Popup a tooltip ne — ty by glitchily létaly */
        .leaflet-popup, .leaflet-tooltip { transition: none !important; }
    </style>
    <script>
    (function () {
        const map = L.map('live-map').setView([49.1951, 16.6068], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(map);

        const markers = new Map(); // vehicleId -> L.marker
        let firstLoad = true;

        function statusColor(v) {
            if (v.status === 'online') return v.is_moving ? '#10b981' : '#3b82f6'; // green moving / blue stopped
            if (v.status === 'recent') return '#f59e0b';                            // amber
            return '#71717a';                                                       // zinc — offline
        }

        function statusDot(v) {
            if (v.status === 'online') return v.is_moving ? 'bg-emerald-500 animate-pulse' : 'bg-blue-500';
            if (v.status === 'recent') return 'bg-amber-500';
            return 'bg-zinc-500';
        }

        function vehicleIcon(v) {
            const color = statusColor(v);

            // S logem značky: kulaté logo + status ring (border) + směrová šipka pokud jede
            if (v.icon_url) {
                const ringOpacity = v.status === 'offline' ? 0.4 : 1;
                const bg = v.icon_bg || 'transparent';
                const arrowHtml = v.is_moving
                    ? `<div style="position:absolute;top:-6px;left:50%;transform:translateX(-50%) rotate(${v.heading}deg);transform-origin:50% 28px;">
                         <svg width="14" height="14" viewBox="0 0 24 24" fill="${color}" stroke="white" stroke-width="2">
                           <path d="M12 2 L19 20 L12 16 L5 20 Z"/>
                         </svg>
                       </div>`
                    : '';
                return L.divIcon({
                    className: '',
                    html: `<div style="position:relative;width:44px;height:44px;opacity:${ringOpacity};">
                        ${arrowHtml}
                        <div style="width:40px;height:40px;border-radius:50%;background:${bg};border:3px solid ${color};box-shadow:0 2px 8px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;position:absolute;top:2px;left:2px;">
                            <img src="${v.icon_url}" width="24" height="24" alt="${v.brand || ''}" />
                        </div>
                    </div>`,
                    iconSize: [44, 44], iconAnchor: [22, 22],
                });
            }

            // Bez loga — fallback na původní šíp/kruh
            const rotation = v.is_moving ? v.heading : 0;
            if (v.status === 'offline') {
                return L.divIcon({
                    className: '',
                    html: `<div style="width:24px;height:24px;display:flex;align-items:center;justify-content:center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="${color}" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="8"/>
                        </svg>
                    </div>`,
                    iconSize: [24, 24], iconAnchor: [12, 12],
                });
            }
            return L.divIcon({
                className: '',
                html: `<div style="transform:rotate(${rotation}deg);width:32px;height:32px;display:flex;align-items:center;justify-content:center">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="${color}" stroke="white" stroke-width="2">
                        <path d="M12 2 L19 20 L12 16 L5 20 Z"/>
                    </svg>
                </div>`,
                iconSize: [32, 32], iconAnchor: [16, 16],
            });
        }

        function popupHtml(v) {
            const statusLabel = v.status === 'online' ? (v.is_moving ? 'V pohybu' : 'Stojí') : (v.status === 'recent' ? 'Recent' : 'Offline');
            const fuelRow = (v.fuel_pct !== null && v.fuel_pct !== undefined)
                ? `<div style="font-size:12px">Palivo: <b>${v.fuel_pct} %</b>${v.fuel_liters !== null && v.fuel_liters !== undefined ? ` · ≈ ${v.fuel_liters} L` : ''}</div>`
                : '';
            const odoRow = (v.odometer_km !== null && v.odometer_km !== undefined)
                ? `<div style="font-size:12px">Tachometr: <b>${v.odometer_km.toLocaleString('cs-CZ')} km</b></div>`
                : '';
            return `
                <div style="min-width:200px">
                    <div style="font-weight:600">${v.name}</div>
                    <div style="font-family:monospace;color:#71717a;font-size:11px">${v.plate || ''} · ${v.imei || ''}</div>
                    ${v.driver ? `<div style="margin-top:6px;font-size:12px">Řidič: ${v.driver}</div>` : ''}
                    <div style="font-size:12px">Stav: <b>${statusLabel}</b> · ${v.speed} km/h</div>
                    ${fuelRow}
                    ${odoRow}
                    <div style="font-size:11px;color:#71717a;margin-top:4px">před ${v.last_seen_ago}</div>
                </div>`;
        }

        function listHtml(items) {
            if (!items.length) return '<div class="px-4 py-6 text-center text-sm text-zinc-500">Žádné vozidlo není aktivní.</div>';
            return items.map(v => `
                <div class="px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 cursor-pointer" data-id="${v.id}">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full ${statusDot(v)}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium truncate">${v.name}</div>
                            <div class="text-xs text-zinc-500 font-mono truncate">${v.plate || ''} · ${v.status === 'online' ? v.speed + ' km/h' : (v.status === 'offline' ? 'offline' : 'recent')}</div>
                        </div>
                        <span class="text-xs text-zinc-500 shrink-0">${v.last_seen_ago}</span>
                    </div>
                </div>`).join('');
        }

        async function refresh() {
            try {
                const r = await fetch('{{ route('api.monitor.latest') }}', { credentials: 'same-origin' });
                if (!r.ok) return;
                const data = await r.json();

                document.getElementById('vehicle-count').textContent = `${data.online_count}/${data.count} online`;
                const list = document.getElementById('vehicle-list');
                list.innerHTML = listHtml(data.vehicles);

                // Update markers
                const seen = new Set();
                data.vehicles.forEach(v => {
                    seen.add(v.id);
                    const latLng = [v.lat, v.lng];
                    if (markers.has(v.id)) {
                        const m = markers.get(v.id);
                        m.setLatLng(latLng).setIcon(vehicleIcon(v)).setPopupContent(popupHtml(v));
                    } else {
                        const m = L.marker(latLng, { icon: vehicleIcon(v) }).bindPopup(popupHtml(v)).addTo(map);
                        markers.set(v.id, m);
                    }
                });
                // Remove markers that vanished (vehicle disabled / device removed)
                for (const [id, m] of markers.entries()) {
                    if (!seen.has(id)) { map.removeLayer(m); markers.delete(id); }
                }
                // Auto-zoom on first load — fit all vehicles incl. offline
                if (firstLoad && data.vehicles.length) {
                    const group = L.featureGroup(Array.from(markers.values()));
                    map.fitBounds(group.getBounds(), { padding: [50, 50], maxZoom: 14 });
                    firstLoad = false;
                }
                // List item click → pan to marker
                document.querySelectorAll('#vehicle-list [data-id]').forEach(el => {
                    el.addEventListener('click', () => {
                        const id = parseInt(el.dataset.id);
                        const m = markers.get(id);
                        if (m) { map.setView(m.getLatLng(), 15); m.openPopup(); }
                    });
                });
            } catch (e) {
                console.warn('Live refresh failed', e);
            }
        }

        refresh();
        setInterval(refresh, 2_000);
    })();
    </script>
@endsection
