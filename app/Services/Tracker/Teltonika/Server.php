<?php

namespace App\Services\Tracker\Teltonika;

use App\Models\Device;
use App\Models\Position;
use App\Services\Tracker\TripService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Server
{
    protected Codec8Decoder $decoder;
    protected TripService $trips;

    public function __construct(
        protected string $bind = '0.0.0.0',
        protected int $port = 5027,
        protected ?\Closure $output = null,
    ) {
        $this->decoder = new Codec8Decoder();
        $this->trips   = new TripService();
    }

    public function run(): void
    {
        $endpoint = "tcp://{$this->bind}:{$this->port}";
        $errno = 0; $errstr = '';
        $server = stream_socket_server($endpoint, $errno, $errstr);
        if (!$server) {
            throw new RuntimeException("stream_socket_server failed: {$errstr} (errno {$errno})");
        }

        $this->log("Listening on {$endpoint}");

        while (true) {
            $client = @stream_socket_accept($server, -1, $peer);
            if (!$client) {
                $this->log('Accept failed, retrying');
                continue;
            }
            $this->log("Connection from {$peer}");

            try {
                $this->handleConnection($client, $peer);
            } catch (\Throwable $e) {
                $this->log("Connection error from {$peer}: " . $e->getMessage());
                Log::channel('single')->error("Connection error from {$peer}", ['ex' => $e]);
            } finally {
                @fclose($client);
                $this->log("Disconnected {$peer}");
            }
        }
    }

    protected function handleConnection($client, string $peer): void
    {
        $peek = @stream_socket_recvfrom($client, 64, STREAM_PEEK); $this->log('First bytes hex: ' . bin2hex((string)$peek) . ' (' . strlen((string)$peek) . ' bytes)'); $lenBytes = $this->readExactly($client, 2);
        $imeiLen  = unpack('n', $lenBytes)[1];
        if ($imeiLen < 1 || $imeiLen > 32) {
            throw new RuntimeException("Invalid IMEI length: {$imeiLen}");
        }
        $imei = $this->readExactly($client, $imeiLen);

        $device = Device::where('imei', $imei)->first();
        if (!$device) {
            fwrite($client, "\x00");
            $this->log("IMEI {$imei} not in DB — rejecting");
            Log::channel('single')->warning('Unknown IMEI', ['imei' => $imei, 'peer' => $peer]);
            return;
        }
        if (!$device->active) {
            fwrite($client, "\x00");
            $this->log("IMEI {$imei} not active — rejecting");
            return;
        }

        fwrite($client, "\x01");
        $this->log("Handshake OK — IMEI {$imei} (device #{$device->id})");

        while (!feof($client)) {
            $header = $this->readExactly($client, 8, allowShort: true);
            if ($header === '') {
                break;
            }
            if (strlen($header) < 8) {
                throw new RuntimeException('Short header: ' . bin2hex($header));
            }
            $dataLen = unpack('N', substr($header, 4, 4))[1];
            $body    = $this->readExactly($client, $dataLen + 4);
            $packet  = $header . $body;

            $decoded = $this->decoder->decode($packet);
            $count   = $decoded['count'];
            $this->log("Packet from IMEI {$imei}: {$count} records (codec 0x" . dechex($decoded['codec']) . ')');

            $this->persistRecords($device, $decoded['records']);

            fwrite($client, pack('N', $count));
        }
    }

    protected function persistRecords(Device $device, array $records): void
    {
        if (empty($records)) return;

        $now      = CarbonImmutable::now();
        $rows     = [];
        $latestTs = null;

        foreach ($records as $r) {
            $rows[] = [
                'device_id'   => $device->id,
                'trip_id'     => null,
                'recorded_at' => $r['timestamp']->toDateTimeString(),
                'latitude'    => $r['latitude'],
                'longitude'   => $r['longitude'],
                'speed'       => $r['speed'],
                'heading'     => $r['angle'],
                'altitude'    => $r['altitude'],
                'satellites'  => $r['satellites'],
                'priority'    => $r['priority'],
                'io_data'     => json_encode($r['io_data']),
                'created_at'  => $now,
            ];
            if (!$latestTs || $r['timestamp']->gt($latestTs)) {
                $latestTs = $r['timestamp'];
            }
        }

        DB::transaction(function () use ($rows, $device, $latestTs) {
            Position::insert($rows);
            $device->forceFill(['last_seen_at' => $latestTs])->save();
            $this->trips->processPositions($device, $rows);
        });

        Log::channel('single')->info('Persisted positions', [
            'device_id' => $device->id,
            'imei'      => $device->imei,
            'count'     => count($rows),
        ]);
    }

    protected function readExactly($sock, int $bytes, bool $allowShort = false): string
    {
        $buf = '';
        while (strlen($buf) < $bytes) {
            $chunk = fread($sock, $bytes - strlen($buf));
            if ($chunk === false || $chunk === '') {
                if ($buf === '' && $allowShort) return '';
                if ($buf === '') throw new RuntimeException('Connection closed during read');
                return $buf;
            }
            $buf .= $chunk;
        }
        return $buf;
    }

    protected function log(string $msg): void
    {
        if ($this->output) {
            ($this->output)($msg);
        }
    }
}
