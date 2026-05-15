<?php

namespace App\Services\Geocoding;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Reverse geocoding via OpenStreetMap Nominatim public endpoint.
 * Rate limit: 1 req/sec. We cache aggressively by rounded coords.
 */
class Nominatim
{
    public const ENDPOINT   = 'https://nominatim.openstreetmap.org/reverse';
    public const USER_AGENT = 'gpsmanager/1.0 (Acme Fleet; admin@example.com)';
    public const CACHE_TTL  = 60 * 60 * 24 * 30; // 30 days
    public const LANG       = 'cs';

    public function reverse(float $lat, float $lng): ?string
    {
        $key = sprintf('geocode:%.4f:%.4f', $lat, $lng);

        return Cache::remember($key, self::CACHE_TTL, function () use ($lat, $lng) {
            return $this->fetch($lat, $lng);
        });
    }

    protected function fetch(float $lat, float $lng): ?string
    {
        $url = self::ENDPOINT . '?' . http_build_query([
            'lat'             => sprintf('%.6f', $lat),
            'lon'             => sprintf('%.6f', $lng),
            'format'          => 'jsonv2',
            'accept-language' => self::LANG,
            'zoom'            => 18,
            'addressdetails'  => 1,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => "User-Agent: " . self::USER_AGENT . "\r\n",
                'timeout'       => 6,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            Log::channel('single')->warning("Nominatim fetch failed for {$lat},{$lng}");
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['address'])) {
            return $data['display_name'] ?? null;
        }

        $addr = $data['address'];
        $street = $addr['road'] ?? $addr['pedestrian'] ?? $addr['footway'] ?? $addr['cycleway'] ?? null;
        $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['municipality'] ?? null;

        if ($street && $city) return "{$street}, {$city}";
        if ($city)             return $city;
        if ($street)           return $street;
        return $data['display_name'] ?? null;
    }
}
