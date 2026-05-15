<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public const TYPES = [
        'poi'    => 'Bod zájmu',
        'garage' => 'Garáž / sídlo',
        'client' => 'Klient',
        'fuel'   => 'Čerpací stanice',
    ];

    protected $fillable = ['name', 'type', 'latitude', 'longitude', 'radius_meters', 'color', 'note', 'active'];

    protected $casts = [
        'latitude'      => 'decimal:7',
        'longitude'     => 'decimal:7',
        'radius_meters' => 'integer',
        'active'        => 'boolean',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
