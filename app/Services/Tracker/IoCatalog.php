<?php

namespace App\Services\Tracker;

/**
 * Resolves Teltonika IO IDs into human-readable label/unit/value.
 * Reads catalog from config/teltonika_io.php.
 */
class IoCatalog
{
    /** @var array<int, array> */
    protected static ?array $catalog = null;

    public static function catalog(): array
    {
        if (self::$catalog === null) {
            self::$catalog = config('teltonika_io', []);
        }
        return self::$catalog;
    }

    public static function meta(int $id): ?array
    {
        return self::catalog()[$id] ?? null;
    }

    public static function label(int $id): string
    {
        return self::meta($id)['label'] ?? "IO {$id}";
    }

    public static function unit(int $id): string
    {
        return self::meta($id)['unit'] ?? '';
    }

    public static function category(int $id): string
    {
        return self::meta($id)['category'] ?? 'unknown';
    }

    /**
     * Scale + format raw IO value to human display string.
     */
    public static function display(int $id, mixed $raw): string
    {
        $meta = self::meta($id);
        if (!$meta) {
            return (string) $raw;
        }

        $scaled = is_numeric($raw) ? ((float) $raw) * ($meta['scale'] ?? 1) : $raw;
        $format = $meta['format'] ?? null;

        $value = match ($format) {
            'bool'      => $scaled ? 'ANO' : 'NE',
            'vin'       => is_string($raw) ? (ctype_xdigit($raw) ? hex2bin($raw) : $raw) : (string) $raw,
            'km_from_m' => number_format($scaled / 1000, 1, ',', ' '),
            null        => (string) (is_int($scaled) || (is_float($scaled) && floor($scaled) == $scaled) ? (int) $scaled : $scaled),
            default     => is_numeric($scaled) ? sprintf($format, $scaled) : (string) $scaled,
        };

        // Override unit for special formats
        $unit = match ($format) {
            'km_from_m' => 'km',
            'bool', 'vin' => '',
            default => $meta['unit'] ?? '',
        };

        return $unit ? trim("{$value} {$unit}") : (string) $value;
    }

    /** Scaled numeric value (without unit) for chart series etc. */
    public static function value(int $id, mixed $raw): float|int|string|null
    {
        if ($raw === null) return null;
        $meta = self::meta($id);
        $scaled = is_numeric($raw) ? ((float) $raw) * ($meta['scale'] ?? 1) : $raw;
        return is_numeric($scaled) ? $scaled : (string) $raw;
    }

    /** Group io_data array by category (system/obd/hev/gsm/meta/unknown). */
    public static function groupByCategory(array $io): array
    {
        $out = ['system' => [], 'obd' => [], 'hev' => [], 'gsm' => [], 'meta' => [], 'unknown' => []];
        foreach ($io as $id => $raw) {
            $cat = self::category((int) $id);
            $out[$cat][(int) $id] = $raw;
        }
        return $out;
    }
}
