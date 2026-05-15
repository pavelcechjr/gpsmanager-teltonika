<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Command;

class BackfillOdometerCommand extends Command
{
    protected $signature = 'gpsmanager:backfill-odometer
                            {vehicle? : Vehicle ID (omit for all)}';

    protected $description = 'Recompute and persist trips.odometer_end_km for trips chronologically since baseline.';

    public function handle(): int
    {
        $vehicles = $this->argument('vehicle')
            ? Vehicle::where('id', $this->argument('vehicle'))->get()
            : Vehicle::all();

        $total = 0;
        foreach ($vehicles as $v) {
            if ($v->odometer_km === null) {
                $this->line("Vehicle #{$v->id} {$v->name}: skipped (no baseline)");
                continue;
            }
            $count = $this->backfill($v);
            $this->info("Vehicle #{$v->id} {$v->name}: backfilled {$count} trips");
            $total += $count;
        }
        $this->info("Total backfilled: {$total}");
        return self::SUCCESS;
    }

    protected function backfill(Vehicle $v): int
    {
        // Chronological merge: trips (closed, since baseline) + calibrations.
        $trips = $v->trips()
            ->whereNotNull('ended_at')
            ->when($v->odometer_updated_at, fn ($q) => $q->where('started_at', '>=', $v->odometer_updated_at))
            ->orderBy('started_at')
            ->get(['id', 'started_at', 'ended_at', 'distance_meters']);

        $calibs = $v->calibrations()
            ->when($v->odometer_updated_at, fn ($q) => $q->where('applied_at', '>=', $v->odometer_updated_at))
            ->orderBy('applied_at')
            ->get(['id', 'applied_at', 'delta_km']);

        // Build chronological event list
        $events = [];
        foreach ($trips as $t) {
            $events[] = ['ts' => $t->ended_at, 'type' => 'trip', 'trip' => $t];
        }
        foreach ($calibs as $c) {
            $events[] = ['ts' => $c->applied_at, 'type' => 'calib', 'calib' => $c];
        }
        usort($events, fn ($a, $b) => $a['ts'] <=> $b['ts']);

        $running = $v->odometer_km;
        $count = 0;
        foreach ($events as $ev) {
            if ($ev['type'] === 'trip') {
                $running += (int) round(($ev['trip']->distance_meters ?? 0) / 1000);
                $ev['trip']->forceFill(['odometer_end_km' => $running])->save();
                $count++;
            } else {
                $running += (int) $ev['calib']->delta_km;
            }
        }
        return $count;
    }
}
