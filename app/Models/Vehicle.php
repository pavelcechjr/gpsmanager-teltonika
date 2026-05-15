<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'name', 'plate', 'color', 'brand', 'fuel_type',
        'default_driver_id', 'device_id',
        'note', 'active',
        'odometer_km', 'odometer_updated_at',
        'fuel_tank_l',
    ];

    /** Druh paliva / pohonu. */
    public const FUEL_TYPES = [
        'petrol'   => 'Benzín',
        'diesel'   => 'Diesel',
        'hybrid'   => 'Hybrid (HEV)',
        'phev'     => 'Plug-in hybrid (PHEV)',
        'electric' => 'Elektromobil (BEV)',
        'lpg'      => 'LPG',
        'cng'      => 'CNG',
    ];

    /** True pokud vozidlo má elektrickou nebo částečně elektrickou propulzi (= eco badge). */
    public function getIsEcoAttribute(): bool
    {
        return in_array($this->fuel_type, ['hybrid', 'phev', 'electric'], true);
    }

    /** Lokalizovaný label paliva (např. "Plug-in hybrid (PHEV)"). */
    public function getFuelTypeLabelAttribute(): ?string
    {
        return $this->fuel_type ? (self::FUEL_TYPES[$this->fuel_type] ?? $this->fuel_type) : null;
    }

    /** Krátký label pro badge — "Hybrid" / "PHEV" / "EV". */
    public function getFuelTypeShortAttribute(): ?string
    {
        return match ($this->fuel_type) {
            'hybrid'   => 'Hybrid',
            'phev'     => 'PHEV',
            'electric' => 'EV',
            default    => null,
        };
    }

    /** Top značek používaných v UI selectu — slug = klíč pro Simple Icons CDN. */
    public const BRANDS = [
        'volkswagen' => 'Volkswagen',
        'skoda'      => 'Škoda',
        'audi'       => 'Audi',
        'seat'       => 'Seat',
        'ford'       => 'Ford',
        'opel'       => 'Opel',
        'renault'    => 'Renault',
        'dacia'      => 'Dacia',
        'peugeot'    => 'Peugeot',
        'citroen'    => 'Citroën',
        'fiat'       => 'Fiat',
        'mercedes'   => 'Mercedes-Benz',
        'bmw'        => 'BMW',
        'toyota'     => 'Toyota',
        'hyundai'    => 'Hyundai',
        'kia'        => 'Kia',
        'nissan'     => 'Nissan',
        'mazda'      => 'Mazda',
        'tesla'      => 'Tesla',
    ];

    /**
     * Per-brand styling pro live mapu — barva loga + background kruhu.
     * Default = bílé logo, transparentní bg (status ring tvoří "obal", mapa prosvítá).
     * Override: Ford = originální modrá na bílém pozadí (zachovat brand identity).
     */
    public function getBrandStyleAttribute(): array
    {
        $slug = strtolower($this->brand ?? '');
        return match ($slug) {
            'ford'    => ['logo_color' => '003478', 'bg' => '#ffffff'], // Ford Oval Blue na bílém
            'bmw'     => ['logo_color' => '0066B1', 'bg' => '#ffffff'], // BMW Blue na bílém
            'tesla'   => ['logo_color' => 'CC0000', 'bg' => '#ffffff'], // Tesla Red na bílém
            default   => ['logo_color' => 'ffffff', 'bg' => 'transparent'], // bílé logo, průhledné
        };
    }

    /** URL na SVG logo značky (Simple Icons CDN). Null pokud brand neznačený. */
    public function getIconUrlAttribute(): ?string
    {
        if (!$this->brand) return null;
        $slug = strtolower($this->brand);
        $color = $this->brand_style['logo_color'];
        return "https://cdn.simpleicons.org/{$slug}/{$color}";
    }

    /** Background kruhu (transparent = mapa prosvítá, #fff = bílý obal). */
    public function getIconBgAttribute(): string
    {
        return $this->brand_style['bg'];
    }

    protected $casts = [
        'active'              => 'boolean',
        'odometer_updated_at' => 'date',
        'fuel_tank_l'         => 'decimal:1',
    ];

    public function defaultDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'default_driver_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function calibrations(): HasMany
    {
        return $this->hasMany(OdometerCalibration::class);
    }

    /**
     * Sum of calibration deltas applied AFTER the baseline date (or all if no baseline).
     */
    public function sumCalibrationDeltaSinceBaseline(): int
    {
        $q = $this->calibrations();
        if ($this->odometer_updated_at) {
            $q->where('applied_at', '>=', $this->odometer_updated_at);
        }
        return (int) $q->sum('delta_km');
    }

    /**
     * Tracked km from trips since baseline (gpsmanager-tracked distance).
     */
    public function getTrackedKmSinceOdometerAttribute(): int
    {
        $sinceMeters = (int) $this->trips()
            ->whereNotNull('ended_at')
            ->when($this->odometer_updated_at, fn ($q) => $q->where('started_at', '>=', $this->odometer_updated_at))
            ->sum('distance_meters');
        return (int) round($sinceMeters / 1000);
    }

    /**
     * Latest OBD odometer reading from car ECU (km), or null if no recent OBD data.
     * Reads io_data['389'] from most recent position of this vehicle's device.
     */
    public function getLatestObdOdometerKmAttribute(): ?int
    {
        if (!$this->device_id) return null;
        $row = Position::where('device_id', $this->device_id)
            ->whereRaw("(io_data->>'389')::int > 0")
            ->orderByDesc('recorded_at')
            ->select('io_data')
            ->limit(1)
            ->first();
        if (!$row) return null;
        $v = $row->io_data['389'] ?? null;
        return is_numeric($v) ? (int) $v : null;
    }

    /**
     * Current odometer — OBD-first (real ECU value), fallback to manual baseline + tracked + calibrations.
     */
    public function getCurrentOdometerKmAttribute(): ?int
    {
        $obd = $this->latest_obd_odometer_km;
        if ($obd !== null) return $obd;

        if ($this->odometer_km === null) return null;
        return $this->odometer_km
             + $this->tracked_km_since_odometer
             + $this->sumCalibrationDeltaSinceBaseline();
    }

    /**
     * Source of current_odometer_km — 'obd' | 'manual' | null.
     */
    public function getOdometerSourceAttribute(): ?string
    {
        if ($this->latest_obd_odometer_km !== null) return 'obd';
        if ($this->odometer_km !== null) return 'manual';
        return null;
    }

    /**
     * Compute the running odometer at a given moment (used for per-trip snapshots).
     * OBD-first: nejbližší position se s OBD odometr ≤ moment. Fallback na manual + tracked + calibrations.
     */
    public function odometerAt(\Carbon\Carbon|\Illuminate\Support\Carbon|\Carbon\CarbonImmutable $moment): ?int
    {
        if ($this->device_id) {
            $row = Position::where('device_id', $this->device_id)
                ->whereRaw("(io_data->>'389')::int > 0")
                ->where('recorded_at', '<=', $moment)
                ->orderByDesc('recorded_at')
                ->select('io_data')
                ->limit(1)
                ->first();
            if ($row) {
                $v = $row->io_data['389'] ?? null;
                if (is_numeric($v)) return (int) $v;
            }
        }

        if ($this->odometer_km === null) return null;
        $from = $this->odometer_updated_at;

        $tripsMeters = (int) $this->trips()
            ->whereNotNull('ended_at')
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from))
            ->where('started_at', '<=', $moment)
            ->sum('distance_meters');

        $calibDelta = (int) $this->calibrations()
            ->when($from, fn ($q) => $q->where('applied_at', '>=', $from))
            ->where('applied_at', '<=', $moment)
            ->sum('delta_km');

        return $this->odometer_km + (int) round($tripsMeters / 1000) + $calibDelta;
    }

    /** Aktuální % paliva z poslední OBD2 packety (PID 0x2F → AVL ID 48). */
    public function getCurrentFuelPctAttribute(): ?int
    {
        if (!$this->device_id) return null;
        $row = Position::where('device_id', $this->device_id)
            ->whereRaw("io_data->>'48' IS NOT NULL")
            ->orderByDesc('recorded_at')
            ->select('io_data')
            ->limit(1)
            ->first();
        return $row ? (int) ($row->io_data['48'] ?? 0) : null;
    }

    /**
     * Aktuální stav paliva v litrech — dopočet z % × tank_l.
     * Pozor: OBD2 % senzor je nelineární u 0% / 100% (dead zone ±5L).
     * Pro přesné litry vyžaduje manufacturer-specific UDS PID (Mode 0x22), nakonfigurováno v Teltonika Configurator USB.
     */
    public function getCurrentFuelLitersAttribute(): ?float
    {
        if (!$this->fuel_tank_l) return null;
        $pct = $this->current_fuel_pct;
        if ($pct === null) return null;
        return round((float) $this->fuel_tank_l * $pct / 100, 1);
    }

    /**
     * Average fuel consumption L/100km over last N closed trips with valid data.
     */
    public function avgFuelConsumption(int $lastTrips = 20): ?float
    {
        $trips = $this->trips()
            ->whereNotNull('ended_at')
            ->whereNotNull('fuel_consumption_l_100km')
            ->where('fuel_consumption_l_100km', '>', 0)
            ->where('fuel_consumption_l_100km', '<', 30)
            ->orderByDesc('started_at')
            ->limit($lastTrips)
            ->pluck('fuel_consumption_l_100km');

        if ($trips->isEmpty()) return null;
        return round($trips->avg(), 2);
    }
}
