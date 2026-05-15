<?php

namespace App\Exports;

use App\Models\Trip;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TripsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected ?Carbon $from = null,
        protected ?Carbon $to = null,
        protected ?int $vehicleId = null,
    ) {
    }

    public function query()
    {
        $q = Trip::query()
            ->with(['vehicle:id,name,plate,brand,fuel_type', 'driver:id,first_name,last_name'])
            ->orderBy('started_at');

        if ($this->vehicleId) $q->where('vehicle_id', $this->vehicleId);
        if ($this->from)      $q->where('started_at', '>=', $this->from);
        if ($this->to)        $q->where('started_at', '<=', $this->to);

        return $q;
    }

    public function headings(): array
    {
        return [
            'Datum',
            'Čas zahájení',
            'Místo zahájení',
            'Čas konce',
            'Místo konce',
            'Doba (h:mm)',
            'Vzdálenost (km)',
            'Max rychlost (km/h)',
            'Vozidlo',
            'SPZ',
            'Řidič',
            'Poznámka',
        ];
    }

    public function map($trip): array
    {
        $duration = $trip->duration_seconds
            ?: ($trip->ended_at && $trip->started_at ? $trip->ended_at->timestamp - $trip->started_at->timestamp : 0);
        $durationStr = $duration
            ? sprintf('%d:%02d', intdiv($duration, 3600), intdiv($duration % 3600, 60))
            : '';

        return [
            $trip->started_at?->format('d.m.Y'),
            $trip->started_at?->format('H:i:s'),
            $trip->start_address,
            $trip->ended_at?->format('H:i:s'),
            $trip->end_address,
            $durationStr,
            $trip->distance_meters ? round($trip->distance_meters / 1000, 2) : null,
            (int) ($trip->max_speed ?? 0),
            $trip->vehicle?->name,
            $trip->vehicle?->plate,
            $trip->driver?->full_name,
            $trip->note,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }
}
