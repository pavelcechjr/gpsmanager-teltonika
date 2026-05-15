<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Device;
use App\Models\Driver;
use App\Models\Position;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->with(['defaultDriver:id,first_name,last_name', 'device:id,imei,model,last_seen_at'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('plate', 'ilike', $term);
                });
            })
            ->when($request->status === 'active',   fn ($q) => $q->where('active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('active', false))
            ->orderBy('name')
            ->orderBy('plate')
            ->paginate(20)
            ->withQueryString();

        return view('vozidla.index', compact('vehicles'));
    }

    public function create(): View
    {
        return view('vozidla.create', [
            'drivers' => $this->driverOptions(),
            'devices' => $this->deviceOptions(),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return redirect()
            ->route('vozidla.index')
            ->with('status', "Vozidlo {$vehicle->name} ({$vehicle->plate}) bylo přidáno.");
    }

    public function show(Vehicle $vozidla): View
    {
        $vozidla->load(['defaultDriver:id,first_name,last_name', 'device:id,imei,model,last_seen_at']);
        $now      = CarbonImmutable::now();
        $monthAgo = $now->startOfMonth();
        $yearAgo  = $now->subYear()->startOfDay();

        // Latest position with OBD telemetry
        $latest = null;
        if ($vozidla->device_id) {
            $latest = Position::where('device_id', $vozidla->device_id)
                ->orderByDesc('recorded_at')
                ->first();
        }

        $totals = [
            'trips_total'         => Trip::where('vehicle_id', $vozidla->id)->whereNotNull('ended_at')->count(),
            'km_total'            => (int) round(Trip::where('vehicle_id', $vozidla->id)->whereNotNull('ended_at')->sum('distance_meters') / 1000),
            'km_today'            => (int) round(Trip::where('vehicle_id', $vozidla->id)->whereDate('started_at', $now->toDateString())->sum('distance_meters') / 1000),
            'km_month'            => (int) round(Trip::where('vehicle_id', $vozidla->id)->where('started_at', '>=', $monthAgo)->sum('distance_meters') / 1000),
            'km_year'             => (int) round(Trip::where('vehicle_id', $vozidla->id)->where('started_at', '>=', $yearAgo)->sum('distance_meters') / 1000),
            'duration_min'        => (int) round(Trip::where('vehicle_id', $vozidla->id)->whereNotNull('ended_at')->sum('duration_seconds') / 60),
            'max_speed'           => (int) (Trip::where('vehicle_id', $vozidla->id)->max('max_speed') ?? 0),
            'odometer_baseline'   => $vozidla->odometer_km,
            'odometer_updated_at' => $vozidla->odometer_updated_at,
            'tracked_since_base'  => $vozidla->tracked_km_since_odometer,
            'current_odometer'    => $vozidla->current_odometer_km,
            'obd_odometer'        => $latest?->obd_odometer, // Teltonika short-term, ne celkový auta
        ];

        // Daily km trend for last 30 days
        $daily = Trip::query()
            ->where('vehicle_id', $vozidla->id)
            ->whereNotNull('ended_at')
            ->where('started_at', '>=', $now->subDays(29)->startOfDay())
            ->selectRaw('DATE(started_at) AS d, SUM(distance_meters)::int AS m, COUNT(*) AS c')
            ->groupBy('d')->orderBy('d')->get()->keyBy(fn ($r) => (string) $r->d);

        $trendKm = []; $trendTrips = []; $trendLabels = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->subDays($i);
            $key = $d->toDateString();
            $row = $daily->get($key);
            $trendKm[]     = $row ? (int) round($row->m / 1000) : 0;
            $trendTrips[] = $row ? (int) $row->c : 0;
            $trendLabels[] = $d->locale('cs_CZ')->isoFormat('D.M.');
        }

        // Voltage trend last 7 days (from positions io_data IO 66)
        $voltageTrend = [];
        if ($vozidla->device_id) {
            $voltageTrend = Position::query()
                ->where('device_id', $vozidla->device_id)
                ->where('recorded_at', '>=', $now->subDays(7))
                ->whereNotNull('io_data')
                ->orderBy('recorded_at')
                ->get(['recorded_at', 'io_data'])
                ->map(fn ($p) => [
                    't' => $p->recorded_at->format('d.m. H:i'),
                    'v' => $p->external_voltage,
                ])
                ->filter(fn ($x) => $x['v'] !== null)
                ->values()
                ->take(500)  // cap for chart sanity
                ->all();
        }

        // Recent trips (10)
        $recentTrips = Trip::query()
            ->where('vehicle_id', $vozidla->id)
            ->with('driver:id,first_name,last_name')
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        return view('vozidla.show', [
            'vehicle'      => $vozidla,
            'totals'       => $totals,
            'latest'       => $latest,
            'trendKm'      => $trendKm,
            'trendTrips'   => $trendTrips,
            'trendLabels'  => $trendLabels,
            'voltageTrend' => $voltageTrend,
            'recentTrips'  => $recentTrips,
        ]);
    }

    public function edit(Vehicle $vozidla): View
    {
        return view('vozidla.edit', [
            'vehicle' => $vozidla,
            'drivers' => $this->driverOptions(),
            'devices' => $this->deviceOptions($vozidla->device_id),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vozidla): RedirectResponse
    {
        $vozidla->update($request->validated());

        return redirect()
            ->route('vozidla.index')
            ->with('status', "Vozidlo {$vozidla->name} ({$vozidla->plate}) bylo upraveno.");
    }

    public function destroy(Vehicle $vozidla): RedirectResponse
    {
        $label = "{$vozidla->name} ({$vozidla->plate})";
        $vozidla->delete();

        return redirect()
            ->route('vozidla.index')
            ->with('status', "Vozidlo {$label} bylo smazáno.");
    }

    /** Active drivers as id => "Příjmení Jméno" map. */
    protected function driverOptions(): array
    {
        return Driver::query()
            ->where('active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($d) => [$d->id => trim($d->last_name . ' ' . $d->first_name)])
            ->all();
    }

    /** Active devices not yet assigned to any vehicle (plus the currently assigned one if editing). */
    protected function deviceOptions(?int $allowDeviceId = null): array
    {
        return Device::query()
            ->where(function ($q) use ($allowDeviceId) {
                $q->where('active', true)
                    ->whereDoesntHave('vehicle');
                if ($allowDeviceId) {
                    $q->orWhere('id', $allowDeviceId);
                }
            })
            ->orderBy('imei')
            ->get(['id', 'imei', 'model'])
            ->mapWithKeys(fn ($d) => [$d->id => $d->imei . ($d->model ? " ({$d->model})" : '')])
            ->all();
    }
}
