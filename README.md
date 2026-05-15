# GPSManager — Open-source fleet management with Teltonika GPS support

Self-hosted fleet tracking & telemetry built on Laravel 13 + PostgreSQL + Redis + Leaflet.
Designed for Teltonika FMC/FMB series GPS trackers using the Codec 8 / Codec 8 Extended protocols.

> Created and maintained by **[NetSERV s.r.o.](https://netserv.cz)** — released under MIT license.

## Features

**Tracking & history**
- TCP listener for Teltonika Codec 8 / 8 Extended (built-in, no external service)
- Automatic trip detection (start when `speed ≥ 3 km/h`, close after configurable idle)
- Reverse geocoding via Nominatim (OpenStreetMap) with 30-day Redis cache
- Trip log ("Drive Book") with PDF & XLSX export (Czech-localized, tax-compliance ready)
- Real-time live map with smooth marker animation (2 s polling)

**OBD2 telemetry (per trip)**
- Total mileage from car ECU (PID 0x31 → AVL ID 389) — replaces "guessed" odometer
- Fuel level (% of tank) → derived liters using `vehicles.fuel_tank_l`
- Per-trip aggregates: max RPM, throttle, engine load, coolant temp, catalyst temp,
  external voltage min/max, max acceleration / deceleration (from GPS speed delta)
- DTC count change detection (Check Engine appeared during trip)
- Engine run time, MIL-on distance

**Hybrid / EV support** (requires Teltonika Configurator setup with manufacturer UDS PIDs)
- Traction Battery SOC, voltage, current, temperature
- Pure-EV mode detection, charging status (AC/DC/done)
- Electric consumption tracking (kWh/100km) alongside fuel consumption
- Eco badge in vehicle lists for hybrid / PHEV / EV vehicles

**Fleet admin**
- Vehicles CRUD with brand logos (Simple Icons CDN — Volkswagen, Ford, BMW, Tesla, …)
- Drivers, devices, groups
- Custom locations with Leaflet geofence picker
- Alarms (9 rule types: speed, geofence enter/exit, idle, voltage, etc.)
- Refueling log, maintenance log
- Multi-user with roles (admin / manager / driver)
- Microsoft 365 SSO (optional — Azure AD)
- Per-trip business/private toggle for tax reporting

## Hardware compatibility

Tested with **Teltonika FMC003** (OBD II dongle).
Should work with any Teltonika unit using **Codec 8 / Codec 8 Extended** binary protocol:
FMB001, FMB010, FMB020, FMB120, FMB125, FMB130, FMB920, FMC130, FMC640, etc.

See [`docs/TELTONIKA_SETUP.md`](docs/TELTONIKA_SETUP.md) for SMS commands and configuration.

## Quick start (Docker)

```bash
git clone https://github.com/pavelcechjr/gpsmanager-teltonika.git
cd gpsmanager-teltonika

# 1. Configure
cp .env.example .env
# Edit .env — set strong DB_PASSWORD and APP_KEY

# 2. Start the stack
docker compose up -d

# 3. Generate APP_KEY (if not set) + run migrations + create admin
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=AdminUserSeeder

# 4. Open in browser
open http://localhost:8085
```

Default admin login: `admin@example.com` / `changeme` (change immediately).

## Teltonika setup

Forward your Teltonika tracker to the listener:

```
SMS to your Teltonika SIM (with 2 spaces before command — auth via empty password):
  "  setparam 2004:your-public-ip-or-domain.com"
  "  setparam 2005:5027"
  "  cpureset"
```

Listener auto-detects IMEI via 17-byte handshake (Codec 8 standard) and registers
the device in the database on first contact. Assign device to vehicle via web UI.

See [`docs/TELTONIKA_SETUP.md`](docs/TELTONIKA_SETUP.md) for full setup,
custom PIDs (engine oil temp, HV battery for hybrids), VAG-specific UDS PIDs.

## Architecture

```
┌─────────────┐  TCP/5027 binary  ┌──────────────┐  Pg JSON  ┌──────────────┐
│ Teltonika   │ ───────────────▶  │ Laravel      │ ────────▶ │ PostgreSQL   │
│ FMC003      │  Codec 8 packets  │ listener +   │           │ + Redis      │
│ (in car)    │                   │ web UI       │           │              │
└─────────────┘                   └──────────────┘           └──────────────┘
                                    ▲      │
                                    │      │ Live update every 2 s
                                    │      ▼
                                  Browser (Leaflet + Alpine + Tailwind dark UI)
```

**Components:**
- **`app/Services/Tracker/Teltonika/`** — Codec 8 binary parser, CRC-16/IBM validation, IMEI handshake
- **`app/Services/Tracker/TripService.php`** — auto trip start/close, distance, agregates
- **`app/Console/Commands/`** — `gpsmanager:listen` (TCP server),
  `gpsmanager:close-stale-trips` (cron 1 min), `gpsmanager:backfill-telemetry`
- **`config/teltonika_io.php`** — AVL ID catalog (label/unit/scale/category)
- **`app/Models/Position.php`** — accessors for fuel, RPM, voltage, OBD odometer, VIN, etc.

## Roadmap

- [ ] HV battery telemetry UI panel auto-fill after Configurator setup
- [ ] Driver behavior scoring (acceleration / braking events / 100 km)
- [ ] Monthly auto-email report (PDF kniha jízd)
- [ ] Multi-tenant support (companies separated by `tenant_id`)
- [ ] Plugin system for other GPS device brands (Concox, Sinotrack, Queclink, …)

## Contributing

Pull requests welcome! For major changes, please open an issue first to discuss.

## License

MIT — see [LICENSE](LICENSE).

## Credits

- Built on [Laravel](https://laravel.com/) (PHP 8.4)
- Maps from [OpenStreetMap](https://openstreetmap.org/) + [Leaflet](https://leafletjs.com/)
- Vehicle brand logos from [Simple Icons](https://simpleicons.org/)
- Icons from [Lucide](https://lucide.dev/)
- Inspired by the protocol research in [eusonlito/GPS-Tracker](https://github.com/eusonlito/GPS-Tracker)
