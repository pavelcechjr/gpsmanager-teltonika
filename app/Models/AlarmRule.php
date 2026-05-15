<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlarmRule extends Model
{
    public const TYPES = [
        'speed_limit'      => 'Překročení rychlosti',
        'voltage_low'      => 'Slabá 12V baterie',
        'dtc_present'      => 'Chybové kódy (DTC)',
        'parking_long'     => 'Dlouhé parkování',
        'device_offline'   => 'Jednotka offline',
        'night_movement'   => 'Pohyb mimo prac. dobu',
        'fuel_low'         => 'Nízká hladina paliva',
        'geofence_enter'   => 'Vstup do oblasti',
        'geofence_exit'    => 'Opuštění oblasti',
        'hv_battery_low'   => 'Nízká HV baterie (hybrid)',
    ];

    public const SEVERITIES = [
        'info'     => 'Info',
        'warn'     => 'Varování',
        'critical' => 'Kritické',
    ];

    protected $fillable = [
        'name', 'type', 'vehicle_id', 'config', 'severity',
        'notify_emails', 'cooldown_min', 'active', 'note',
    ];

    protected $casts = [
        'config'        => 'array',
        'notify_emails' => 'array',
        'active'        => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AlarmEvent::class, 'rule_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        return ($this->config[$key] ?? null) ?? $default;
    }
}
