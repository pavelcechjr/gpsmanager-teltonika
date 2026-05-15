<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTripRequest;
use App\Models\Driver;
use App\Models\OdometerCalibration;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripController extends Controller
{
    public function index(Request $request): View
    {
        $defaultFrom = now()->subDay();
        $defaultTo   = now();

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : $defaultFrom;
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()    : $defaultTo;
        $vehicleId = $request->integer('vehicle_id') ?: null;
        $typeFilter = $request->string('type')->toString() ?: null; // 'private' | 'business' | null

        $trips = Trip::query()
            ->with(['vehicle:id,name,plate,brand,fuel_type', 'driver:id,first_name,last_name'])
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to)
            ->when($typeFilter === 'private',  fn ($q) => $q->where('is_private', true))
            ->when($typeFilter === 'business', fn ($q) => $q->where('is_private', false))
            ->orderByDesc('started_at')
            ->paginate(50)
            ->withQueryString();

        // Calibrations to interleave chronologically (skip if type filter set)
        $calibrations = collect();
        if (!$typeFilter) {
            $calibrations = OdometerCalibration::query()
                ->with('vehicle:id,name,plate,brand,fuel_type')
                ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
                ->where('applied_at', '>=', $from)
                ->where('applied_at', '<=', $to)
                ->orderByDesc('applied_at')
                ->get();
        }

        // Totals for header summary
        $totalsQuery = Trip::query()
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to)
            ->whereNotNull('ended_at');

        $totals = [
            'business_km' => (int) round((clone $totalsQuery)->where('is_private', false)->sum('distance_meters') / 1000),
            'private_km'  => (int) round((clone $totalsQuery)->where('is_private', true)->sum('distance_meters') / 1000),
            'business_count' => (clone $totalsQuery)->where('is_private', false)->count(),
            'private_count'  => (clone $totalsQuery)->where('is_private', true)->count(),
        ];

        $vehicles = Vehicle::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'plate']);

        return view('kniha-jizd.index', [
            'trips'        => $trips,
            'calibrations' => $calibrations,
            'totals'       => $totals,
            'vehicles'     => $vehicles,
            'fromInput'    => $from->format('Y-m-d'),
            'toInput'      => $to->format('Y-m-d'),
            'vehicleId'    => $vehicleId,
            'typeFilter'   => $typeFilter,
            'defaultRange' => !$request->hasAny(['from', 'to', 'vehicle_id', 'type']),
        ]);
    }

    public function toggleType(Trip $trip): RedirectResponse
    {
        $trip->update(['is_private' => !$trip->is_private]);
        return back()->with('status', $trip->is_private ? 'Jízda označena jako soukromá.' : 'Jízda označena jako služební.');
    }

    public function show(Trip $trip): View
    {
        $trip->load([
            'vehicle:id,name,plate,brand,fuel_type,color',
            'driver:id,first_name,last_name',
            'device:id,imei,model',
        ]);

        $positions = $trip->positions()
            ->orderBy('recorded_at')
            ->get();

        $stats = [
            'avg_speed' => $positions->count()
                ? (int) round($positions->avg('speed'))
                : 0,
            'max_alt'   => (int) ($positions->max('altitude') ?? 0),
            'positions' => $positions->count(),
        ];

        // Polyline data for Leaflet: [{lat, lng, speed}, ...] for speed-colored segments.
        // Use display_speed (OBD if available, else GPS) so polyline color reflects more accurate value.
        $polyline = $positions
            ->map(fn ($p) => [
                'lat'   => (float) $p->latitude,
                'lng'   => (float) $p->longitude,
                'speed' => (int) $p->display_speed,
            ])
            ->all();

        // ── Telemetry aggregations from io_data ───────────────────────────
        $telemetry = $this->aggregateTelemetry($positions);

        // Voltage series for chart (over trip duration)
        $voltageSeries = $positions->map(fn ($p) => [
            't' => $p->recorded_at->format('H:i:s'),
            'v' => $p->external_voltage,
        ])->filter(fn ($x) => $x['v'] !== null)->values()->all();

        $rpmSeries = $positions->map(fn ($p) => [
            't' => $p->recorded_at->format('H:i:s'),
            'v' => $p->engine_rpm,
        ])->filter(fn ($x) => $x['v'] !== null)->values()->all();

        $speedSeries = $positions->map(fn ($p) => [
            't'   => $p->recorded_at->format('H:i:s'),
            'gps' => (int) $p->speed,
            'obd' => $p->obd_speed,
        ])->values()->all();

        return view('kniha-jizd.show', [
            'trip'          => $trip,
            'positions'     => $positions,
            'polyline'      => $polyline,
            'stats'         => $stats,
            'telemetry'     => $telemetry,
            'voltageSeries' => $voltageSeries,
            'rpmSeries'     => $rpmSeries,
            'speedSeries'   => $speedSeries,
        ]);
    }

    public function edit(Trip $trip): View
    {
        $drivers = Driver::query()
            ->where('active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($d) => [$d->id => trim($d->last_name . ' ' . $d->first_name)])
            ->all();

        return view('kniha-jizd.edit', compact('trip', 'drivers'));
    }

    public function update(UpdateTripRequest $request, Trip $trip): RedirectResponse
    {
        $trip->update($request->validated());

        return redirect()
            ->route('kniha-jizd.show', $trip)
            ->with('status', 'Jízda byla upravena.');
    }

    /**
     * Aggregate telemetry across all positions in trip.
     */
    protected function aggregateTelemetry($positions): array
    {
        if ($positions->isEmpty()) {
            return [];
        }

        $first = $positions->first();
        $last  = $positions->last();

        $values = function (string $attr) use ($positions) {
            return $positions->pluck($attr)->filter(fn ($v) => $v !== null);
        };

        $aggregate = function ($collection, callable $op) {
            return $collection->isEmpty() ? null : $op($collection);
        };

        return [
            // Aggregations
            'rpm_max'           => $aggregate($values('engine_rpm'),     fn ($c) => (int) $c->max()),
            'rpm_avg'           => $aggregate($values('engine_rpm'),     fn ($c) => (int) round($c->avg())),
            'speed_obd_max'     => $aggregate($values('obd_speed'),      fn ($c) => (int) $c->max()),
            'speed_gps_max'     => $aggregate($values('speed'),           fn ($c) => (int) $c->max()),
            'coolant_max'       => $aggregate($values('coolant_temp'),    fn ($c) => (int) $c->max()),
            'throttle_max'      => $aggregate($values('throttle'),        fn ($c) => (int) $c->max()),
            'engine_load_max'   => $aggregate($values('engine_load'),     fn ($c) => (int) $c->max()),
            'dtc_max'           => $aggregate($values('dtc_count'),       fn ($c) => (int) $c->max()),
            'ext_voltage_min'   => $aggregate($values('external_voltage'),fn ($c) => round($c->min(), 2)),
            'ext_voltage_max'   => $aggregate($values('external_voltage'),fn ($c) => round($c->max(), 2)),
            'int_voltage_min'   => $aggregate($values('internal_battery'),fn ($c) => round($c->min(), 2)),

            // Snapshots — bere první/poslední NENULOVOU hodnotu napříč trip.
            // (Klíček-off packety na konci trip mají null OBD data, nelze brát $first/$last.)
            'fuel_start'        => $values('fuel_level')->first(),
            'fuel_end'          => $values('fuel_level')->last(),
            'fuel_delta'        => $values('fuel_level')->isNotEmpty()
                ? $values('fuel_level')->first() - $values('fuel_level')->last()
                : null,
            'odo_start'         => $values('obd_odometer')->first(),
            'odo_end'           => $values('obd_odometer')->last(),
            'vin'               => $values('vin')->last() ?: $values('vin')->first(),

            // Counts
            'records'           => $positions->count(),
            'movement_records'  => $positions->filter(fn ($p) => $p->moving === true)->count(),
            'ignition_records'  => $positions->filter(fn ($p) => $p->ignition === true)->count(),
        ];
    }
}
