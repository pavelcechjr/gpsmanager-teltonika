<?php

namespace App\Console\Commands;

use App\Services\Tracker\Teltonika\Server;
use Illuminate\Console\Command;

class ListenTeltonika extends Command
{
    protected $signature = 'gpsmanager:listen
                            {--port=5027 : TCP port to listen on}
                            {--bind=0.0.0.0 : Address to bind to}';

    protected $description = 'Start the Teltonika Codec 8 TCP listener.';

    public function handle(): int
    {
        $port = (int) $this->option('port');
        $bind = (string) $this->option('bind');

        $this->info("gpsmanager listener starting on {$bind}:{$port}");
        $this->line('Press Ctrl+C to stop.');
        $this->line('');

        $server = new Server($bind, $port, function (string $msg) {
            $this->line('[' . now()->format('Y-m-d H:i:s') . '] ' . $msg);
        });

        $server->run();

        return self::SUCCESS;
    }
}
