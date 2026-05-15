<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'note', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function vehiclesAsDefault(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'default_driver_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
