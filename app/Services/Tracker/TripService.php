<?php

namespace App\Services\Tracker;

use App\Models\Device;
use App\Models\Position;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\Alarms\AlarmService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Decides when a Trip starts / extends based on incoming positions.
 *
 * Rules:
 *  - Trip starts at first position with speed >= START_SPEED_KMH (if no open trip yet).
 *  - Open trip is extended by every subsequent position (max_speed updated, trip_id linked).
 *  - Trip is *closed* by CloseStaleTripsCommand (cron) when last position is older than IDLE_MIN.
 */
class TripService
{
    public const START_SPEED_KMH = 3;

    public function processPositions(Device $device, array $rows): void
    {
        if (empty($rows)) return;
        $vehicle = $device->vehicle()->first();
        if (!$vehicle) {
            // No vehicle attached → no Trip context. Positions still saved as raw.
            return;
        }

        // Ensure ASC chronological order for state machine.
        usort($rows, fn ($a, $b) => strcmp($a['recorded_at'], $b['recorded_at']));

        $openTrip = Trip::where('vehicle_id', $vehicle->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        foreach ($rows as $row) {
            $ts    = $row['recorded_at'];
            $speed = (int) ($row['speed'] ?? 0);
            $lat   = (float) $row['latitude'];
            $lng   = (float) $row['longitude'];

            // Start a new trip when there's no open one and the vehicle is actually moving.
            if (!$openTrip && $speed >= self::START_SPEED_KMH) {
                $openTrip = Trip::create([
                    'vehicle_id' => $vehicle->id,
                    'device_id'  => $device->id,
                    'driver_id'  => $vehicle->default_driver_id,
                    'started_at' => $ts,
                    'start_lat'  => $lat,
                    'start_lng'  => $lng,
                    'max_speed'  => $speed,
                ]);
            }

            if ($openTrip) {
                // Link position record to the trip (insert happened before this call).
                Position::where('device_id', $device->id)
                    ->where('recorded_at', $ts)
                    ->whereNull('trip_id')
                    ->update(['trip_id' => $openTrip->id]);

                if ($speed > (int) $openTrip->max_speed) {
                    $openTrip->forceFill(['max_speed' => $speed])->save();
                }
            }
        }

        // ── Alarm rule evaluation per position ───────────────────────────
        try {
            $alarms = app(AlarmService::class);
            $persisted = Position::where('device_id', $device->id)
                ->whereIn('recorded_at', array_map(fn ($r) => $r['recorded_at'], $rows))
                ->orderBy('recorded_at')
                ->get();
            foreach ($persisted as $position) {
                $alarms->evaluatePosition($vehicle, $position);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('single')
                ->error('Alarm evaluation failed', ['vehicle_id' => $vehicle->id, 'ex' => $e->getMessage()]);
        }
    }
}
