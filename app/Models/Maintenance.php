<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    public const TYPES = [
        'servis'    => 'Servis',
        'stk'       => 'STK / EK',
        'pojisteni' => 'Pojištění',
        'olej'      => 'Výměna oleje',
        'pneu'      => 'Výměna pneu',
        'jine'      => 'Jiné',
    ];

    protected $fillable = ['vehicle_id', 'type', 'planned_at', 'performed_at', 'mileage_km', 'price', 'supplier', 'note'];

    protected $casts = [
        'planned_at'   => 'date',
        'performed_at' => 'date',
        'price'        => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusAttribute(): string
    {
        if ($this->performed_at) return 'done';
        if ($this->planned_at && $this->planned_at->isPast()) return 'overdue';
        if ($this->planned_at) return 'planned';
        return 'draft';
    }
}
