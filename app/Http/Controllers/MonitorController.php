<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MonitorController extends Controller
{
    /** Vehicle is "online" — last packet within this window (live tracking, smooth marker). */
    protected const ONLINE_MIN = 10;
    /** Vehicle is "recent" — last packet within this window (still relevant, yellow marker). */
    protected const RECENT_MIN = 240; // 4h

    public function index(): View
    {
        return view('monitor.index');
    }

    public function latest(): JsonResponse
    {
        $online = CarbonImmutable::now()->subMinutes(self::ONLINE_MIN);
        $recent = CarbonImmutable::now()->subMinutes(self::RECENT_MIN);

        // Vrátíme VŠECHNA aktivní vozidla s přiřazeným device, bez ohledu na stale —
        // klient (mapa) je všechna vykreslí a barvou rozlišuje status.
        $vehicles = Vehicle::query()
            ->where('active', true)
            ->whereNotNull('device_id')
            ->with(['device:id,imei,model,last_seen_at', 'defaultDriver:id,first_name,last_name'])
            ->get();

        $items = $vehicles->map(function ($v) use ($online, $recent) {
            if (!$v->device) return null;
            $pos = Position::where('device_id', $v->device->id)
                ->orderByDesc('recorded_at')
                ->first();
            if (!$pos) return null;

            $lastSeen = $v->device->last_seen_at;
            $status = 'offline';
            if ($lastSeen && $lastSeen >= $online)      $status = 'online';
            elseif ($lastSeen && $lastSeen >= $recent)  $status = 'recent';

            return [
                'id'            => $v->id,
                'name'          => $v->name,
                'plate'         => $v->plate,
                'color'         => $v->color,
                'brand'         => $v->brand,
                'icon_url'      => $v->icon_url,
                'icon_bg'       => $v->icon_bg,
                'driver'        => $v->defaultDriver?->full_name,
                'imei'          => $v->device->imei,
                'model'         => $v->device->model,
                'lat'           => (float) $pos->latitude,
                'lng'           => (float) $pos->longitude,
                'speed'         => (int) $pos->speed,
                'heading'       => (int) $pos->heading,
                'recorded_at'   => $pos->recorded_at->toIso8601String(),
                'last_seen_ago' => $pos->recorded_at->diffForHumans(['short' => true]),
                'status'        => $status,                       // online | recent | offline
                'is_moving'     => $status === 'online' && $pos->speed > 3,
                'fuel_pct'      => $v->current_fuel_pct,
                'fuel_liters'   => $v->current_fuel_liters,
                'odometer_km'   => $v->current_odometer_km,
            ];
        })->filter()->values();

        $onlineCount = $items->where('status', 'online')->count();

        return response()->json([
            'timestamp'    => now()->toIso8601String(),
            'count'        => $items->count(),
            'online_count' => $onlineCount,
            'vehicles'     => $items,
        ]);
    }
}
