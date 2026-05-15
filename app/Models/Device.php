<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $fillable = ['imei', 'phone_number', 'model', 'config', 'active', 'last_seen_at'];

    protected $casts = [
        'config' => 'array',
        'active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function vehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
