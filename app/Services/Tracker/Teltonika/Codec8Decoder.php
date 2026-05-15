<?php

namespace App\Services\Tracker\Teltonika;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Decodes Teltonika Codec 8 (and Codec 8 Extended) AVL data packets.
 *
 * Packet structure (after IMEI handshake):
 *  +0  4B  Preamble (always 0x00000000)
 *  +4  4B  Data field length (uint32 BE) — bytes 8..(8+len-1) is the data field
 *  +8  1B  Codec ID (0x08 = Codec 8, 0x8E = Codec 8 Extended)
 *  +9  1B  Number of records (N)
 *  ... N AVL records ...
 *  +?  1B  Number of records (repeat, must match)
 *  +?  4B  CRC-16/IBM over data field (bytes 8..(8+len-1))
 *
 * Reference: https://wiki.teltonika-gps.com/view/Codec
 */
class Codec8Decoder
{
    public const CODEC_8     = 0x08;
    public const CODEC_8_EXT = 0x8E;

    /**
     * Decode the binary packet body. Returns ['count' => int, 'records' => array<int, array>].
     *
     * Each record has: timestamp (Carbon), priority, longitude, latitude, altitude, angle,
     * satellites, speed, event_io_id, io_data (assoc array by IO id).
     *
     * @throws RuntimeException on malformed packet
     */
    public function decode(string $packet): array
    {
        if (strlen($packet) < 16) {
            throw new RuntimeException('Packet too short: ' . strlen($packet) . ' bytes');
        }

        if (substr($packet, 0, 4) !== "\x00\x00\x00\x00") {
            throw new RuntimeException('Invalid preamble: ' . bin2hex(substr($packet, 0, 4)));
        }

        $dataLength = unpack('N', substr($packet, 4, 4))[1];
        $expected   = 8 + $dataLength + 4;
        if (strlen($packet) < $expected) {
            throw new RuntimeException("Packet truncated: have " . strlen($packet) . ", expected {$expected}");
        }

        $data    = substr($packet, 8, $dataLength);
        $crcRecv = unpack('N', substr($packet, 8 + $dataLength, 4))[1];
        $crcCalc = $this->crc16Ibm($data);
        if ($crcRecv !== $crcCalc) {
            throw new RuntimeException(sprintf('CRC mismatch: received 0x%08X, calculated 0x%08X', $crcRecv, $crcCalc));
        }

        $codecId    = ord($data[0]);
        $numRecords = ord($data[1]);
        $extended   = ($codecId === self::CODEC_8_EXT);
        if ($codecId !== self::CODEC_8 && !$extended) {
            throw new RuntimeException(sprintf('Unsupported codec: 0x%02X', $codecId));
        }

        $records = [];
        $offset  = 2;
        for ($i = 0; $i < $numRecords; $i++) {
            [$record, $offset] = $this->decodeRecord($data, $offset, $extended);
            $records[] = $record;
        }

        $tailNum = ord($data[$offset] ?? "\x00");
        if ($tailNum !== $numRecords) {
            throw new RuntimeException("Tail record count mismatch: header={$numRecords}, tail={$tailNum}");
        }

        return ['count' => $numRecords, 'records' => $records, 'codec' => $codecId];
    }

    protected function decodeRecord(string $data, int $offset, bool $extended): array
    {
        $tsMs      = unpack('J', substr($data, $offset, 8))[1];   $offset += 8;
        $priority  = ord($data[$offset]);                          $offset += 1;
        $longitude = $this->signedInt32(substr($data, $offset, 4)); $offset += 4;
        $latitude  = $this->signedInt32(substr($data, $offset, 4)); $offset += 4;
        $altitude  = $this->signedInt16(substr($data, $offset, 2)); $offset += 2;
        $angle     = unpack('n', substr($data, $offset, 2))[1];    $offset += 2;
        $sats      = ord($data[$offset]);                          $offset += 1;
        $speed     = unpack('n', substr($data, $offset, 2))[1];    $offset += 2;

        // IO Element
        if ($extended) {
            $eventIoId = unpack('n', substr($data, $offset, 2))[1]; $offset += 2;
            $totalIo   = unpack('n', substr($data, $offset, 2))[1]; $offset += 2;
        } else {
            $eventIoId = ord($data[$offset]);                       $offset += 1;
            $totalIo   = ord($data[$offset]);                       $offset += 1;
        }

        $io = [];
        foreach ([1, 2, 4, 8] as $byteSize) {
            $countLen = $extended ? 2 : 1;
            $n = $extended
                ? unpack('n', substr($data, $offset, 2))[1]
                : ord($data[$offset]);
            $offset += $countLen;

            for ($k = 0; $k < $n; $k++) {
                $idLen = $extended ? 2 : 1;
                $id = $extended
                    ? unpack('n', substr($data, $offset, 2))[1]
                    : ord($data[$offset]);
                $offset += $idLen;

                $raw = substr($data, $offset, $byteSize);
                $offset += $byteSize;
                $io[$id] = match ($byteSize) {
                    1       => ord($raw),
                    2       => unpack('n', $raw)[1],
                    4       => unpack('N', $raw)[1],
                    8       => unpack('J', $raw)[1],
                };
            }
        }

        // Codec 8 Extended also has variable-length IO (byteSize=X) — for MVP skip if codec 8 only.
        if ($extended) {
            $nVar = unpack('n', substr($data, $offset, 2))[1]; $offset += 2;
            for ($k = 0; $k < $nVar; $k++) {
                $id  = unpack('n', substr($data, $offset, 2))[1]; $offset += 2;
                $len = unpack('n', substr($data, $offset, 2))[1]; $offset += 2;
                $val = substr($data, $offset, $len);              $offset += $len;
                $io[$id] = bin2hex($val);
            }
        }

        return [[
            'timestamp'    => CarbonImmutable::createFromTimestampMs($tsMs)->setTimezone(config('app.timezone', 'Europe/Prague')),
            'priority'     => $priority,
            'longitude'    => $longitude / 10_000_000,
            'latitude'     => $latitude / 10_000_000,
            'altitude'     => $altitude,
            'angle'        => $angle,
            'satellites'   => $sats,
            'speed'        => $speed,
            'event_io_id'  => $eventIoId,
            'io_data'      => $io,
        ], $offset];
    }

    protected function signedInt32(string $bytes): int
    {
        $v = unpack('N', $bytes)[1];
        return $v >= 0x80000000 ? $v - 0x100000000 : $v;
    }

    protected function signedInt16(string $bytes): int
    {
        $v = unpack('n', $bytes)[1];
        return $v >= 0x8000 ? $v - 0x10000 : $v;
    }

    /**
     * Teltonika uses CRC-16/IBM (poly 0xA001, init 0x0000) returned as a 32-bit BE int
     * with the high 16 bits set to zero.
     */
    public function crc16Ibm(string $data): int
    {
        $crc = 0;
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $crc ^= ord($data[$i]);
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 1) ? (($crc >> 1) ^ 0xA001) : ($crc >> 1);
            }
        }
        return $crc & 0xFFFF;
    }
}
