<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $now = CarbonImmutable::now();

        $rangeKey = $request->string('range')->toString() ?: 'month';
        [$from, $to, $label] = match ($rangeKey) {
            'week'  => [$now->subDays(6)->startOfDay(),  $now->endOfDay(),  'Posledních 7 dní'],
            'year'  => [$now->subMonths(11)->startOfMonth(), $now->endOfMonth(), 'Posledních 12 měsíců'],
            'all'   => [CarbonImmutable::create(2000)->startOfDay(), $now->endOfDay(), 'Celá historie'],
            default => [$now->subDays(29)->startOfDay(), $now->endOfDay(), 'Posledních 30 dní'],
        };

        $base = Trip::query()
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$from, $to]);

        $totals = [
            'trips'    => (clone $base)->count(),
            'km'       => (int) round((clone $base)->sum('distance_meters') / 1000),
            'minutes'  => (int) round((clone $base)->sum('duration_seconds') / 60),
            'max_kmh'  => (int) ((clone $base)->max('max_speed') ?? 0),
        ];

        // Per driver
        $perDriver = (clone $base)
            ->selectRaw('driver_id, COUNT(*) AS trips, COALESCE(SUM(distance_meters),0)::int AS meters, COALESCE(SUM(duration_seconds),0)::int AS seconds')
            ->whereNotNull('driver_id')
            ->groupBy('driver_id')
            ->orderByDesc('meters')
            ->with('driver:id,first_name,last_name')
            ->limit(20)
            ->get();

        // Per vehicle
        $perVehicle = (clone $base)
            ->selectRaw('vehicle_id, COUNT(*) AS trips, COALESCE(SUM(distance_meters),0)::int AS meters, COALESCE(SUM(duration_seconds),0)::int AS seconds, MAX(max_speed) AS max_speed')
            ->whereNotNull('vehicle_id')
            ->groupBy('vehicle_id')
            ->orderByDesc('meters')
            ->with('vehicle:id,name,plate,brand,fuel_type')
            ->limit(20)
            ->get();

        // Daily km trend (last 30 days)
        $trendRows = Trip::query()
            ->whereNotNull('ended_at')
            ->where('started_at', '>=', $now->subDays(29)->startOfDay())
            ->selectRaw('DATE(started_at) AS day, COALESCE(SUM(distance_meters),0)::int AS meters, COUNT(*) AS trips')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy(fn ($r) => (string) $r->day);

        $trendKm     = [];
        $trendTrips  = [];
        $trendLabels = [];
        for ($i = 29; $i >= 0; $i--) {
            $d   = $now->subDays($i);
            $key = $d->toDateString();
            $r   = $trendRows->get($key);
            $trendKm[]     = $r ? (int) round($r->meters / 1000) : 0;
            $trendTrips[]  = $r ? (int) $r->trips : 0;
            $trendLabels[] = $d->locale('cs_CZ')->isoFormat('D.M.');
        }

        return view('kniha-jizd.statistiky', compact(
            'totals', 'perDriver', 'perVehicle',
            'trendKm', 'trendTrips', 'trendLabels',
            'rangeKey', 'label', 'from', 'to',
        ));
    }
}
