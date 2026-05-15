<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\View\View;

class AdminPageController extends Controller
{
    public function servery(): View
    {
        return view('admin.servery', [
            'stats' => [
                'active_devices' => Device::where('active', true)->count(),
                'positions_24h'  => \App\Models\Position::where('recorded_at', '>=', now()->subDay())->count(),
                'trips_open'     => Trip::whereNull('ended_at')->count(),
            ],
        ]);
    }

    public function konfigurace(): View
    {
        return view('admin.konfigurace');
    }

    public function casoveZony(): View
    {
        $zones = collect(\DateTimeZone::listIdentifiers())
            ->filter(fn ($tz) => str_contains($tz, '/'))
            ->groupBy(fn ($tz) => explode('/', $tz)[0]);

        return view('admin.casove-zony', compact('zones'));
    }
}
