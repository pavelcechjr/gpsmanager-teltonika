<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlarmEvent extends Model
{
    protected $fillable = [
        'rule_id', 'vehicle_id', 'position_id', 'trip_id',
        'triggered_at', 'resolved_at', 'severity', 'summary',
        'data', 'notified',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at'  => 'datetime',
        'data'         => 'array',
        'notified'     => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlarmRule::class, 'rule_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->resolved_at !== null;
    }
}
