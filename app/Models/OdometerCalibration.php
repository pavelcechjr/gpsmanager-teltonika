<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdometerCalibration extends Model
{
    protected $fillable = ['vehicle_id', 'applied_at', 'delta_km', 'note', 'user_id'];

    protected $casts = [
        'applied_at' => 'datetime',
        'delta_km'   => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
