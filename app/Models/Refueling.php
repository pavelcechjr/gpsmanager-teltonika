<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refueling extends Model
{
    protected $fillable = ['vehicle_id', 'driver_id', 'fueled_at', 'liters', 'price_total', 'mileage_km', 'fuel_type', 'station', 'note'];

    protected $casts = [
        'fueled_at'   => 'datetime',
        'liters'      => 'decimal:2',
        'price_total' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function getPricePerLiterAttribute(): ?float
    {
        $liters = (float) $this->liters;
        return $liters > 0 ? round((float) $this->price_total / $liters, 2) : null;
    }
}
