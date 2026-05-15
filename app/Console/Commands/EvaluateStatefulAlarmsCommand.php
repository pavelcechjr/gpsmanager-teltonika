<?php

namespace App\Console\Commands;

use App\Services\Alarms\AlarmService;
use Illuminate\Console\Command;

class EvaluateStatefulAlarmsCommand extends Command
{
    protected $signature = 'gpsmanager:evaluate-alarms';

    protected $description = 'Evaluate stateful alarm rules (parking_long, device_offline). Run from cron every 5 min.';

    public function handle(AlarmService $alarms): int
    {
        $created = $alarms->evaluateStateful();
        $this->info("Created {$created} alarm event(s).");
        return self::SUCCESS;
    }
}
