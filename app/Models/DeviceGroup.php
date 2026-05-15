<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeviceGroup extends Model
{
    protected $fillable = ['name', 'color', 'description'];

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_group_device');
    }
}
