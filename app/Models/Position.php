<?php

namespace App\Models;

use App\Services\Tracker\IoCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'trip_id', 'recorded_at',
        'latitude', 'longitude', 'speed', 'heading', 'altitude',
        'satellites', 'priority', 'io_data',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'io_data' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get raw IO value (or default).
     */
    public function io(int $id, mixed $default = null): mixed
    {
        return $this->io_data[(string) $id] ?? $this->io_data[$id] ?? $default;
    }

    /**
     * Get scaled IO value (applies config scale, e.g. mV → V).
     */
    public function ioValue(int $id): float|int|string|null
    {
        $raw = $this->io($id);
        return $raw === null ? null : IoCatalog::value($id, $raw);
    }

    // ── Convenience getters (named) ───────────────────────────────────────

    public function getObdSpeedAttribute(): ?int
    {
        $v = $this->io(37);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getEngineRpmAttribute(): ?int
    {
        $v = $this->io(36);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getCoolantTempAttribute(): ?int
    {
        $v = $this->io(32);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getCoolantTemp2Attribute(): ?int
    {
        $v = $this->io(57);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getEngineOilTempAttribute(): ?int
    {
        $v = $this->io(92);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getIntakeAirTempAttribute(): ?int
    {
        $v = $this->io(39);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getCatalystTempAttribute(): ?int
    {
        $v = $this->io(116);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getFuelLevelAttribute(): ?int
    {
        $v = $this->io(48);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getThrottleAttribute(): ?int
    {
        $v = $this->io(41);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getEngineLoadAttribute(): ?int
    {
        $v = $this->io(31);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getDtcCountAttribute(): ?int
    {
        $v = $this->io(30);
        return is_numeric($v) ? (int) $v : null;
    }

    public function getExternalVoltageAttribute(): ?float
    {
        $v = $this->io(66);
        return is_numeric($v) ? round((float) $v / 1000, 3) : null;
    }

    public function getInternalBatteryAttribute(): ?float
    {
        $v = $this->io(67);
        return is_numeric($v) ? round((float) $v / 1000, 3) : null;
    }

    public function getIgnitionAttribute(): ?bool
    {
        $v = $this->io(239);
        return $v === null ? null : (bool) $v;
    }

    public function getMovingAttribute(): ?bool
    {
        $v = $this->io(240);
        return $v === null ? null : (bool) $v;
    }

    public function getVinAttribute(): ?string
    {
        $v = $this->io(256);
        if (!is_string($v)) return null;
        if (ctype_xdigit($v) && strlen($v) % 2 === 0) {
            return hex2bin($v) ?: $v;
        }
        return $v;
    }

    /**
     * Total mileage from car ECU via OBD2 PID 0x31 — value in km, matches dashboard.
     * Mapped to AVL ID 389 (verified against Golf MK8 baseline 94 000 km → io 389 = 94 115).
     */
    public function getObdOdometerAttribute(): ?int
    {
        $v = $this->io(389);
        return is_numeric($v) && (int) $v > 0 ? (int) $v : null;
    }

    /** Engine run time (s) since start — io 42. */
    public function getRunTimeSecondsAttribute(): ?int
    {
        $v = $this->io(42);
        return is_numeric($v) ? (int) $v : null;
    }

    /** Distance traveled with MIL (check engine light) on — io 43, km. */
    public function getMilDistanceKmAttribute(): ?int
    {
        $v = $this->io(43);
        return is_numeric($v) ? (int) $v : null;
    }

    /** Teltonika-internal odometer counted from GPS km since install — io 16, meters. */
    public function getInternalOdometerKmAttribute(): ?int
    {
        $v = $this->io(16);
        return is_numeric($v) ? (int) round($v / 1000) : null;
    }

    /** Preferred speed: OBD if available + meaningful, else GPS Doppler. */
    public function getDisplaySpeedAttribute(): int
    {
        $obd = $this->obd_speed;
        if ($obd !== null && $obd > 0) return $obd;
        return (int) $this->speed;
    }
}
