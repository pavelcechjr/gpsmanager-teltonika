<?php

namespace App\Console\Commands;

use App\Models\Position;
use App\Models\Trip;
use Illuminate\Console\Command;

/**
 * Backfill per-trip telemetry aggregates (max RPM, voltage min/max, DTC change, ...)
 * Used after migration adding the new columns — projde uzavřené trips a spočítá hodnoty
 * z jejich positions io_data. Bezpečně idempotentní — overwrite=true volitelně.
 */
class BackfillTelemetryCommand extends Command
{
    protected $signature = 'gpsmanager:backfill-telemetry
                            {--overwrite : Přepsat i už vyplněné hodnoty (default: jen prázdné)}';

    protected $description = 'Backfill per-trip telemetry agregátů (max_rpm, voltage, DTC change, …) ze starších uzavřených trips.';

    public function handle(): int
    {
        $overwrite = (bool) $this->option('overwrite');

        $query = Trip::whereNotNull('ended_at');
        if (!$overwrite) {
            // Jen trips které ještě nemají žádné nové agregáty
            $query->whereNull('max_rpm')
                  ->whereNull('voltage_max')
                  ->whereNull('dtc_change');
        }
        $total = $query->count();
        $this->info("Backfilling {$total} trips (overwrite=" . ($overwrite ? 'yes' : 'no') . ")…");

        $done = 0;
        $query->chunk(50, function ($trips) use (&$done) {
            foreach ($trips as $trip) {
                $this->computeAndSave($trip);
                $done++;
                if ($done % 25 === 0) $this->info("  … {$done} done");
            }
        });

        $this->info("Dokončeno: {$done} trips updated.");
        return self::SUCCESS;
    }

    protected function computeAndSave(Trip $trip): void
    {
        $positions = Position::where('trip_id', $trip->id)
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'speed', 'io_data']);
        if ($positions->count() < 2) return;

        $maxRpm = 0; $maxThrottle = 0; $maxEngineLoad = 0; $maxObdSpeed = 0;
        $maxAccel = 0.0; $maxDecel = 0.0;
        $coolantMin = null; $coolantMax = null; $catalystMax = null;
        $voltMin = null; $voltMax = null;
        $dtcStart = null; $dtcEnd = null;
        $runTimeStart = null; $runTimeEnd = null;
        $fuelStart = null; $fuelEnd = null;
        $odoEnd = null;

        $prev = null;
        foreach ($positions as $p) {
            $io = $p->io_data ?? [];
            $getIo = fn (string $k) => isset($io[$k]) && is_numeric($io[$k]) ? (float) $io[$k] : null;

            if ($prev) {
                $dt = $p->recorded_at->timestamp - $prev->recorded_at->timestamp;
                if ($dt > 0 && $dt < 30) {
                    $a = ((float) $p->speed - (float) $prev->speed) / 3.6 / $dt;
                    if ($a > $maxAccel && $a < 10) $maxAccel = $a;
                    if ($a < $maxDecel && $a > -10) $maxDecel = $a;
                }
            }

            if (($v = $getIo('36')) !== null && $v < 10000) $maxRpm = max($maxRpm, (int) $v);
            if (($v = $getIo('41')) !== null)               $maxThrottle = max($maxThrottle, (int) $v);
            if (($v = $getIo('31')) !== null)               $maxEngineLoad = max($maxEngineLoad, (int) $v);
            if (($v = $getIo('37')) !== null)               $maxObdSpeed = max($maxObdSpeed, (int) $v);

            if (($v = $getIo('32')) !== null) {
                $coolantMin = $coolantMin === null ? (int) $v : min($coolantMin, (int) $v);
                $coolantMax = $coolantMax === null ? (int) $v : max($coolantMax, (int) $v);
            }
            if (($v = $getIo('57')) !== null) {
                $catalystMax = $catalystMax === null ? (int) $v : max($catalystMax, (int) $v);
            }
            if (($v = $getIo('66')) !== null) {
                $volts = $v / 1000.0;
                if ($volts > 5 && $volts < 50) {
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
            // Fuel — bere poslední nenulovou hodnotu (kvůli ignition-off packetům na konci trip)
            if (($v = $getIo('48')) !== null) {
                if ($fuelStart === null) $fuelStart = (int) $v;
                $fuelEnd = (int) $v;
            }
            // OBD odometer — stejně bere poslední nenulovou
            if (($v = $getIo('389')) !== null && $v > 0) {
                $odoEnd = (int) $v;
            }
            $prev = $p;
        }

        $dtcChange     = ($dtcStart !== null && $dtcEnd !== null) ? $dtcEnd - $dtcStart : null;
        $engineRunTime = ($runTimeStart !== null && $runTimeEnd !== null && $runTimeEnd >= $runTimeStart)
            ? $runTimeEnd - $runTimeStart : null;

        // Fuel consumption
        $fuelConsumedL = null;
        $fuelL100km    = null;
        $tankL         = $trip->vehicle?->fuel_tank_l;
        if ($fuelStart !== null && $fuelEnd !== null && $tankL && $fuelStart >= $fuelEnd) {
            $delta = $fuelStart - $fuelEnd;
            $fuelConsumedL = round($delta * (float) $tankL / 100, 2);
            if ($trip->distance_meters > 50) {
                $km = $trip->distance_meters / 1000;
                $fuelL100km = round($fuelConsumedL * 100 / $km, 2);
                if ($fuelL100km > 50) $fuelL100km = null;
            }
        }

        $trip->forceFill([
            'max_rpm'              => $maxRpm > 0 ? $maxRpm : null,
            'max_throttle_pct'     => $maxThrottle > 0 ? $maxThrottle : null,
            'max_engine_load_pct'  => $maxEngineLoad > 0 ? $maxEngineLoad : null,
            'max_obd_speed'        => $maxObdSpeed > 0 ? $maxObdSpeed : null,
            'max_acceleration_ms2' => $maxAccel > 0.1 ? round($maxAccel, 2) : null,
            'max_deceleration_ms2' => $maxDecel < -0.1 ? round($maxDecel, 2) : null,
            'coolant_temp_min'     => $coolantMin,
            'coolant_temp_max'     => $coolantMax,
            'catalyst_temp_max'    => $catalystMax,
            'dtc_change'           => $dtcChange,
            'voltage_min'          => $voltMin !== null ? round($voltMin, 2) : null,
            'voltage_max'          => $voltMax !== null ? round($voltMax, 2) : null,
            'engine_run_time_s'    => $engineRunTime,
            // Plus retroaktivní oprava fuel + odometer (ignition-off packety dříve smazaly)
            'fuel_start_pct'           => $fuelStart,
            'fuel_end_pct'             => $fuelEnd,
            'fuel_consumed_l'          => $fuelConsumedL,
            'fuel_consumption_l_100km' => $fuelL100km,
            'odometer_end_km'          => $odoEnd ?? $trip->odometer_end_km,
        ])->save();
    }
}
