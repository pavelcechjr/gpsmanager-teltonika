<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $now   = CarbonImmutable::now();
        $today = $now->startOfDay();

        // ─── KPI cards ──────────────────────────────────────────────────────
        $kpi = [
            'active_trips'  => Trip::whereNull('ended_at')->count(),
            'trips_today'   => Trip::whereDate('started_at', $now->toDateString())->count(),
            'km_today'      => (int) round(
                Trip::whereDate('started_at', $now->toDateString())->sum('distance_meters') / 1000
            ),
            'online_devs'   => Device::where('active', true)
                ->where('last_seen_at', '>=', $now->subMinutes(5))
                ->count(),
            'total_active_devs' => Device::where('active', true)->count(),
            'total_vehicles' => Vehicle::where('active', true)->count(),
            'total_drivers' => \App\Models\Driver::where('active', true)->count(),
        ];

        // ─── km / day chart (last 14 days) ──────────────────────────────────
        $kmDailyRows = Trip::query()
            ->selectRaw('DATE(started_at) AS day, SUM(distance_meters)::int AS meters, COUNT(*) AS trips')
            ->where('started_at', '>=', $now->subDays(13)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy(fn ($r) => (string) $r->day);

        $kmDaily = [];
        $tripsDaily = [];
        $kmLabels = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = $now->subDays($i)->startOfDay();
            $key = $d->toDateString();
            $row = $kmDailyRows->get($key);
            $kmDaily[]    = $row ? (int) round($row->meters / 1000) : 0;
            $tripsDaily[] = $row ? (int) $row->trips : 0;
            $kmLabels[]   = $d->locale('cs_CZ')->isoFormat('D.M.');
        }

        // ─── Top vehicles by trip count, last 7 days ────────────────────────
        $topVehicles = Trip::query()
            ->selectRaw('vehicle_id, COUNT(*) AS trip_count, SUM(distance_meters)::int AS total_m')
            ->where('started_at', '>=', $now->subDays(7))
            ->whereNotNull('vehicle_id')
            ->groupBy('vehicle_id')
            ->orderByDesc('trip_count')
            ->limit(5)
            ->with('vehicle:id,name,plate,brand,fuel_type')
            ->get();

        // ─── Device status breakdown ────────────────────────────────────────
        $devStatus = [
            'online'  => Device::where('active', true)
                ->where('last_seen_at', '>=', $now->subMinutes(5))->count(),
            'recent'  => Device::where('active', true)
                ->where('last_seen_at', '>=', $now->subDay())
                ->where('last_seen_at', '<',  $now->subMinutes(5))->count(),
            'stale'   => Device::where('active', true)
                ->where('last_seen_at', '<', $now->subDay())->count(),
            'never'   => Device::where('active', true)
                ->whereNull('last_seen_at')->count(),
            'inactive' => Device::where('active', false)->count(),
        ];

        // ─── Recent trips (last 8) ──────────────────────────────────────────
        $recentTrips = Trip::with(['vehicle:id,name,plate,brand,fuel_type', 'driver:id,first_name,last_name'])
            ->orderByDesc('started_at')
            ->limit(8)
            ->get();

        // ─── Online vehicles right now ──────────────────────────────────────
        $onlineVehicles = Vehicle::query()
            ->where('active', true)
            ->whereHas('device', fn ($q) => $q
                ->where('active', true)
                ->where('last_seen_at', '>=', $now->subMinutes(5)))
            ->with('device:id,imei,model,last_seen_at')
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            'kpi',
            'kmDaily', 'tripsDaily', 'kmLabels',
            'topVehicles',
            'devStatus',
            'recentTrips',
            'onlineVehicles',
        ));
    }
}
