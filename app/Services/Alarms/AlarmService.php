<?php

namespace App\Services\Alarms;

use App\Models\AlarmEvent;
use App\Models\AlarmRule;
use App\Models\Position;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class AlarmService
{
    /**
     * Evaluate all active rules for the given vehicle against a fresh position.
     * Called from TripService after Position insert.
     */
    public function evaluatePosition(Vehicle $vehicle, Position $position): void
    {
        $rules = AlarmRule::query()
            ->where('active', true)
            ->where(function ($q) use ($vehicle) {
                $q->whereNull('vehicle_id')
                    ->orWhere('vehicle_id', $vehicle->id);
            })
            ->get();

        foreach ($rules as $rule) {
            try {
                $hit = match ($rule->type) {
                    'speed_limit'    => $this->checkSpeedLimit($rule, $position),
                    'voltage_low'    => $this->checkVoltageLow($rule, $vehicle, $position),
                    'dtc_present'    => $this->checkDtcPresent($rule, $position),
                    'fuel_low'       => $this->checkFuelLow($rule, $position),
                    'night_movement' => $this->checkNightMovement($rule, $position),
                    'hv_battery_low' => null, // future, depends on Configurator setup
                    default          => null, // parking_long, device_offline, geofence handled by scheduled cron
                };

                if (!$hit) continue;
                if ($this->inCooldown($rule, $vehicle)) continue;

                $this->createEvent($rule, $vehicle, $position, $hit['summary'], $hit['data'] ?? []);
            } catch (\Throwable $e) {
                Log::channel('single')->error('Alarm rule eval failed', [
                    'rule_id' => $rule->id,
                    'type'    => $rule->type,
                    'ex'      => $e->getMessage(),
                ]);
            }
        }
    }

    /** Cron-driven checks (offline / parking_long / hv_battery_low) — invoked by gpsmanager:evaluate-alarms */
    public function evaluateStateful(): int
    {
        $now = CarbonImmutable::now();
        $created = 0;

        $rules = AlarmRule::query()->where('active', true)->whereIn('type', [
            'parking_long', 'device_offline',
        ])->get();

        foreach ($rules as $rule) {
            $vehicles = $rule->vehicle_id
                ? Vehicle::where('id', $rule->vehicle_id)->where('active', true)->get()
                : Vehicle::where('active', true)->get();

            foreach ($vehicles as $vehicle) {
                if ($this->inCooldown($rule, $vehicle)) continue;

                $hit = match ($rule->type) {
                    'parking_long'   => $this->checkParkingLong($rule, $vehicle, $now),
                    'device_offline' => $this->checkDeviceOffline($rule, $vehicle, $now),
                    default          => null,
                };

                if (!$hit) continue;

                $this->createEvent($rule, $vehicle, null, $hit['summary'], $hit['data'] ?? []);
                $created++;
            }
        }

        return $created;
    }

    // ── Rule evaluators ──────────────────────────────────────────────────

    protected function checkSpeedLimit(AlarmRule $rule, Position $p): ?array
    {
        $limit = (int) $rule->configValue('limit_kmh', 130);
        $obd = $p->obd_speed;
        $gps = (int) $p->speed;
        $actual = $obd && $obd > 0 ? $obd : $gps;
        if ($actual <= $limit) return null;
        return [
            'summary' => "Překročena rychlost: {$actual} km/h (limit {$limit})",
            'data'    => ['speed' => $actual, 'limit' => $limit, 'lat' => (float) $p->latitude, 'lng' => (float) $p->longitude],
        ];
    }

    protected function checkVoltageLow(AlarmRule $rule, Vehicle $vehicle, Position $p): ?array
    {
        $minV = (float) $rule->configValue('min_volt', 12.0);
        $durationMin = (int) $rule->configValue('duration_min', 5);
        $v = $p->external_voltage;
        if ($v === null || $v >= $minV) return null;

        // Sustained: all positions in last duration_min are below min_volt.
        $since = CarbonImmutable::parse($p->recorded_at)->subMinutes($durationMin);
        $countAbove = Position::where('device_id', $p->device_id)
            ->where('recorded_at', '>=', $since)
            ->where('recorded_at', '<=', $p->recorded_at)
            ->get()
            ->filter(fn ($x) => $x->external_voltage !== null && $x->external_voltage >= $minV)
            ->count();

        if ($countAbove > 0) return null;

        return [
            'summary' => sprintf('Slabá 12V baterie: %.2f V (< %.1f V už %d min)', $v, $minV, $durationMin),
            'data'    => ['voltage' => $v, 'min' => $minV, 'duration_min' => $durationMin],
        ];
    }

    protected function checkDtcPresent(AlarmRule $rule, Position $p): ?array
    {
        $dtc = $p->dtc_count;
        if ($dtc === null || $dtc < 1) return null;
        return [
            'summary' => "Auto hlásí {$dtc} chybový kód(y) (DTC)",
            'data'    => ['dtc_count' => $dtc],
        ];
    }

    protected function checkFuelLow(AlarmRule $rule, Position $p): ?array
    {
        $threshold = (int) $rule->configValue('percent', 15);
        $fuel = $p->fuel_level;
        if ($fuel === null || $fuel > $threshold) return null;
        return [
            'summary' => "Nízká hladina paliva: {$fuel} %",
            'data'    => ['fuel_level' => $fuel, 'threshold' => $threshold],
        ];
    }

    protected function checkNightMovement(AlarmRule $rule, Position $p): ?array
    {
        $start = $rule->configValue('start_time', '22:00');
        $end   = $rule->configValue('end_time', '05:00');
        $movement = $p->moving;
        if (!$movement) return null;

        $ts = $p->recorded_at;
        $hm = $ts->format('H:i');
        // Range crosses midnight if end < start
        $inWindow = $start <= $end
            ? ($hm >= $start && $hm <= $end)
            : ($hm >= $start || $hm <= $end);
        if (!$inWindow) return null;

        return [
            'summary' => "Pohyb mimo pracovní dobu ({$start}–{$end})",
            'data'    => ['time' => $hm, 'window' => "{$start}-{$end}"],
        ];
    }

    protected function checkParkingLong(AlarmRule $rule, Vehicle $vehicle, CarbonImmutable $now): ?array
    {
        $hours = (int) $rule->configValue('hours', 24);
        if (!$vehicle->device_id) return null;
        $lastIgnitionOn = Position::where('device_id', $vehicle->device_id)
            ->whereRaw("(io_data->>'239')::int = 1")
            ->orderByDesc('recorded_at')
            ->first();
        if (!$lastIgnitionOn) return null;
        $hoursSince = $now->diffInHours($lastIgnitionOn->recorded_at);
        if ($hoursSince < $hours) return null;
        return [
            'summary' => "Vozidlo {$vehicle->name} stojí už {$hoursSince} h",
            'data'    => ['hours' => $hoursSince, 'last_ignition' => $lastIgnitionOn->recorded_at->toIso8601String()],
        ];
    }

    protected function checkDeviceOffline(AlarmRule $rule, Vehicle $vehicle, CarbonImmutable $now): ?array
    {
        $threshold = (int) $rule->configValue('threshold_min', 30);
        $device = $vehicle->device;
        if (!$device) return null;
        if (!$device->last_seen_at) return null;
        $minSince = $now->diffInMinutes($device->last_seen_at);
        if ($minSince < $threshold) return null;
        return [
            'summary' => "Jednotka {$device->imei} offline {$minSince} min",
            'data'    => ['minutes' => $minSince],
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    protected function inCooldown(AlarmRule $rule, Vehicle $vehicle): bool
    {
        if ($rule->cooldown_min <= 0) return false;
        $since = CarbonImmutable::now()->subMinutes($rule->cooldown_min);
        return AlarmEvent::where('rule_id', $rule->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('triggered_at', '>=', $since)
            ->exists();
    }

    protected function createEvent(AlarmRule $rule, Vehicle $vehicle, ?Position $position, string $summary, array $data = []): AlarmEvent
    {
        $event = AlarmEvent::create([
            'rule_id'      => $rule->id,
            'vehicle_id'   => $vehicle->id,
            'position_id'  => $position?->id,
            'trip_id'      => $position?->trip_id,
            'triggered_at' => $position?->recorded_at ?? now(),
            'severity'     => $rule->severity,
            'summary'      => $summary,
            'data'         => $data,
        ]);

        Log::channel('single')->info('Alarm triggered', [
            'rule'    => $rule->name,
            'type'    => $rule->type,
            'vehicle' => $vehicle->name,
            'summary' => $summary,
        ]);

        // Email notification — basic, expand later (M365 Graph driver)
        if (!empty($rule->notify_emails)) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Vozidlo: {$vehicle->name} ({$vehicle->plate})\n\n{$summary}\n\nČas: " . $event->triggered_at->format('d.m.Y H:i:s'),
                    function ($m) use ($rule, $event) {
                        $m->to($rule->notify_emails)
                          ->subject("[gpsmanager] {$event->severity} — {$rule->name}");
                    }
                );
                $event->update(['notified' => true]);
            } catch (\Throwable $e) {
                Log::channel('single')->warning('Alarm email failed', ['ex' => $e->getMessage()]);
            }
        }

        return $event;
    }
}
