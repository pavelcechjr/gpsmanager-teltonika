<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    protected $fillable = [
        'vehicle_id', 'device_id', 'driver_id',
        'started_at', 'ended_at',
        'start_lat', 'start_lng', 'start_address',
        'end_lat', 'end_lng', 'end_address',
        'distance_meters', 'duration_seconds', 'max_speed', 'note',
        'fuel_consumed_l', 'fuel_consumption_l_100km', 'fuel_start_pct', 'fuel_end_pct',
        'is_private', 'odometer_end_km',
        // Per-trip telemetry agregáty
        'max_rpm', 'max_throttle_pct', 'max_engine_load_pct', 'max_obd_speed',
        'max_acceleration_ms2', 'max_deceleration_ms2',
        'coolant_temp_min', 'coolant_temp_max', 'catalyst_temp_max',
        'dtc_change', 'voltage_min', 'voltage_max', 'engine_run_time_s',
    ];

    protected $casts = [
        'started_at'               => 'datetime',
        'ended_at'                 => 'datetime',
        'start_lat'                => 'decimal:7',
        'start_lng'                => 'decimal:7',
        'end_lat'                  => 'decimal:7',
        'end_lng'                  => 'decimal:7',
        'fuel_consumed_l'          => 'decimal:2',
        'fuel_consumption_l_100km' => 'decimal:2',
        'is_private'               => 'boolean',
        'odometer_end_km'          => 'integer',
        'max_acceleration_ms2'     => 'decimal:2',
        'max_deceleration_ms2'     => 'decimal:2',
        'voltage_min'              => 'decimal:2',
        'voltage_max'              => 'decimal:2',
    ];

    /** Heuristika: anomálie hodné varování v Knize jízd (badge). */
    public function getWarningsAttribute(): array
    {
        $w = [];
        if ($this->dtc_change !== null && $this->dtc_change > 0) {
            $w[] = ['type' => 'dtc', 'label' => "+{$this->dtc_change} DTC", 'severity' => 'red'];
        }
        if ($this->coolant_temp_max !== null && $this->coolant_temp_max >= 105) {
            $w[] = ['type' => 'overheat', 'label' => "{$this->coolant_temp_max} °C", 'severity' => 'red'];
        }
        if ($this->voltage_min !== null && (float) $this->voltage_min < 12.0) {
            $w[] = ['type' => 'low_volt', 'label' => "{$this->voltage_min} V", 'severity' => 'amber'];
        }
        if ($this->voltage_max !== null && (float) $this->voltage_max > 15.2) {
            $w[] = ['type' => 'overcharge', 'label' => "{$this->voltage_max} V", 'severity' => 'amber'];
        }
        if ($this->max_acceleration_ms2 !== null && (float) $this->max_acceleration_ms2 > 4.0) {
            $w[] = ['type' => 'hard_accel', 'label' => "{$this->max_acceleration_ms2} m/s²", 'severity' => 'amber'];
        }
        if ($this->max_deceleration_ms2 !== null && (float) $this->max_deceleration_ms2 < -4.0) {
            $w[] = ['type' => 'hard_brake', 'label' => "{$this->max_deceleration_ms2} m/s²", 'severity' => 'amber'];
        }
        if ($this->max_rpm !== null && $this->max_rpm > 5000) {
            $w[] = ['type' => 'high_rpm', 'label' => "{$this->max_rpm} rpm", 'severity' => 'amber'];
        }
        return $w;
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
