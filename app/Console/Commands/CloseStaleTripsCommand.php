<?php

namespace App\Console\Commands;

use App\Models\Position;
use App\Models\Trip;
use App\Services\Geocoding\Nominatim;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseStaleTripsCommand extends Command
{
    protected $signature = 'gpsmanager:close-stale-trips
                            {--idle-min=5 : Minutes without new position before closing a trip}';

    protected $description = 'Close open trips whose vehicle stopped sending positions for N minutes. Computes distance, duration, geocodes start/end addresses.';

    public function handle(Nominatim $geo): int
    {
        $idleMin   = (int) $this->option('idle-min');
        $threshold = CarbonImmutable::now()->subMinutes($idleMin);

        $closed = 0;
        Trip::query()
            ->whereNull('ended_at')
            ->get()
            ->each(function (Trip $trip) use (&$closed, $geo, $threshold) {
                $last = Position::where('trip_id', $trip->id)
                    ->orderByDesc('recorded_at')
                    ->first();
                if (!$last) return; // not enough positions

                if ($last->recorded_at->gt($threshold)) {
                    // Trip is still active (recent positions).
                    return;
                }

                $this->closeTrip($trip, $geo);
                $closed++;
            });

        $this->info("Closed {$closed} stale trip(s).");
        return self::SUCCESS;
    }

    protected function closeTrip(Trip $trip, Nominatim $geo): void
    {
        $positions = Position::where('trip_id', $trip->id)
            ->orderBy('recorded_at')
            ->get(['id', 'recorded_at', 'latitude', 'longitude', 'speed', 'io_data']);

        if ($positions->count() < 2) {
            $trip->forceFill([
                'ended_at'         => $positions->last()?->recorded_at ?? $trip->started_at,
                'end_lat'          => $positions->last()?->latitude,
                'end_lng'          => $positions->last()?->longitude,
                'distance_meters'  => 0,
                'duration_seconds' => 0,
            ])->save();
            return;
        }

        // ── Single-pass agregace ────────────────────────────────────────────
        $distance    = 0.0;
        $maxSpeed    = (int) ($trip->max_speed ?? 0);
        $maxRpm      = 0; $maxThrottle = 0; $maxEngineLoad = 0; $maxObdSpeed = 0;
        $maxAccel    = 0.0; $maxDecel = 0.0;
        $coolantMin  = null; $coolantMax = null; $catalystMax = null;
        $voltMin     = null; $voltMax = null;
        $dtcStart    = null; $dtcEnd = null;
        $runTimeStart = null; $runTimeEnd = null;
        $fuelStart   = null; $fuelEnd = null;

        $prev = null;
        foreach ($positions as $p) {
            $io = $p->io_data ?? [];

            // Haversine + max speed
            if ($prev) {
                $distance += $this->haversineMeters(
                    (float) $prev->latitude, (float) $prev->longitude,
                    (float) $p->latitude,    (float) $p->longitude,
                );
                // Acceleration / deceleration z GPS speed delta (m/s²).
                $dt = $p->recorded_at->timestamp - $prev->recorded_at->timestamp;
                if ($dt > 0 && $dt < 30) { // ignore gaps > 30s (sleep, no signal)
                    $prevSpeedMs = (float) $prev->speed / 3.6;
                    $currSpeedMs = (float) $p->speed / 3.6;
                    $a = ($currSpeedMs - $prevSpeedMs) / $dt;
                    if ($a > $maxAccel && $a < 10) $maxAccel = $a;   // sanity clamp
                    if ($a < $maxDecel && $a > -10) $maxDecel = $a;
                }
            }
            if ((int) $p->speed > $maxSpeed) $maxSpeed = (int) $p->speed;

            // OBD agregace — pole io_data jsou string keys
            $getIo = fn (string $k) => isset($io[$k]) && is_numeric($io[$k]) ? (float) $io[$k] : null;

            if (($v = $getIo('36')) !== null && $v < 10000) $maxRpm        = max($maxRpm, (int) $v);
            if (($v = $getIo('41')) !== null)               $maxThrottle   = max($maxThrottle, (int) $v);
            if (($v = $getIo('31')) !== null)               $maxEngineLoad = max($maxEngineLoad, (int) $v);
            if (($v = $getIo('37')) !== null)               $maxObdSpeed   = max($maxObdSpeed, (int) $v);

            if (($v = $getIo('32')) !== null) {
                $coolantMin = $coolantMin === null ? (int) $v : min($coolantMin, (int) $v);
                $coolantMax = $coolantMax === null ? (int) $v : max($coolantMax, (int) $v);
            }
            if (($v = $getIo('57')) !== null) {
                $catalystMax = $catalystMax === null ? (int) $v : max($catalystMax, (int) $v);
            }
            if (($v = $getIo('66')) !== null) {
                $volts = $v / 1000.0;
                if ($volts > 5 && $volts < 50) { // sanity
                    $voltMin = $voltMin === null ? $volts : min($voltMin, $volts);
                    $voltMax = $voltMax === null ? $volts : max($voltMax, $volts);
                }
            }
            if (($v = $getIo('30')) !== null) {
                if ($dtcStart === null) $dtcStart = (int) $v;
                $dtcEnd = (int) $v;
            }
            if (($v = $getIo('42')) !== null) {
                if ($runTimeStart === null) $runTimeStart = (int) $v;
                $runTimeEnd = (int) $v;
            }
            if (($v = $getIo('48')) !== null) {
                if ($fuelStart === null) $fuelStart = (int) $v;
                $fuelEnd = (int) $v;  // průběžně přepíše, finálně = poslední přečtená hodnota
            }

            $prev = $p;
        }

        $first = $positions->first();
        $last  = $positions->last();
        $duration = $last->recorded_at->timestamp - $first->recorded_at->timestamp;

        $startAddr = $geo->reverse((float) $first->latitude, (float) $first->longitude);
        $endAddr   = $geo->reverse((float) $last->latitude,  (float) $last->longitude);

        // Fuel consumption (vyžaduje tank + oba %)
        $fuelConsumedL = null;
        $fuelL100km    = null;
        $tankL         = $trip->vehicle?->fuel_tank_l;
        if ($fuelStart !== null && $fuelEnd !== null && $tankL && $fuelStart >= $fuelEnd) {
            $delta = $fuelStart - $fuelEnd;
            $fuelConsumedL = round($delta * (float) $tankL / 100, 2);
            if ($distance > 50) {
                $km = $distance / 1000;
                $fuelL100km = round($fuelConsumedL * 100 / $km, 2);
                if ($fuelL100km > 50) $fuelL100km = null;
            }
        }

        // Odometer — OBD-first
        $odoEnd = $trip->vehicle?->odometerAt($last->recorded_at);

        // Delta hodnoty
        $dtcChange     = ($dtcStart !== null && $dtcEnd !== null) ? $dtcEnd - $dtcStart : null;
        $engineRunTime = ($runTimeStart !== null && $runTimeEnd !== null && $runTimeEnd >= $runTimeStart)
            ? $runTimeEnd - $runTimeStart : null;

        $trip->forceFill([
            'ended_at'                 => $last->recorded_at,
            'start_lat'                => $first->latitude,
            'start_lng'                => $first->longitude,
            'start_address'            => $startAddr,
            'end_lat'                  => $last->latitude,
            'end_lng'                  => $last->longitude,
            'end_address'              => $endAddr,
            'distance_meters'          => (int) round($distance),
            'duration_seconds'         => $duration,
            'odometer_end_km'          => $odoEnd,
            'max_speed'                => $maxSpeed,
            'fuel_start_pct'           => $fuelStart,
            'fuel_end_pct'             => $fuelEnd,
            'fuel_consumed_l'          => $fuelConsumedL,
            'fuel_consumption_l_100km' => $fuelL100km,
            // Nové telemetry agregáty
            'max_rpm'                  => $maxRpm > 0 ? $maxRpm : null,
            'max_throttle_pct'         => $maxThrottle > 0 ? $maxThrottle : null,
            'max_engine_load_pct'      => $maxEngineLoad > 0 ? $maxEngineLoad : null,
            'max_obd_speed'            => $maxObdSpeed > 0 ? $maxObdSpeed : null,
            'max_acceleration_ms2'     => $maxAccel > 0.1 ? round($maxAccel, 2) : null,
            'max_deceleration_ms2'     => $maxDecel < -0.1 ? round($maxDecel, 2) : null,
            'coolant_temp_min'         => $coolantMin,
            'coolant_temp_max'         => $coolantMax,
            'catalyst_temp_max'        => $catalystMax,
            'dtc_change'               => $dtcChange,
            'voltage_min'              => $voltMin !== null ? round($voltMin, 2) : null,
            'voltage_max'              => $voltMax !== null ? round($voltMax, 2) : null,
            'engine_run_time_s'        => $engineRunTime,
        ])->save();
    }

    /** Great-circle distance between two coords in meters. */
    protected function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
