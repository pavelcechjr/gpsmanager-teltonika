<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $drivers = Driver::query()
            ->withCount('vehiclesAsDefault')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('first_name', 'ilike', $term)
                        ->orWhere('last_name', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term)
                        ->orWhere('phone', 'ilike', $term);
                });
            })
            ->when($request->status === 'active', fn ($q) => $q->where('active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('active', false))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('ridici.index', compact('drivers'));
    }

    public function create(): View
    {
        return view('ridici.create');
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $driver = Driver::create($request->validated());

        return redirect()
            ->route('ridici.index')
            ->with('status', "Řidič {$driver->full_name} byl vytvořen.");
    }

    public function show(Driver $ridici, Request $request): View
    {
        $defaultFrom = now()->subDays(29);
        $defaultTo   = now();

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : $defaultFrom;
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()    : $defaultTo;

        $trips = Trip::query()
            ->with(['vehicle:id,name,plate,brand,fuel_type'])
            ->where('driver_id', $ridici->id)
            ->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to)
            ->orderByDesc('started_at')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'count'   => Trip::where('driver_id', $ridici->id)->whereBetween('started_at', [$from, $to])->count(),
            'km'      => (int) round(Trip::where('driver_id', $ridici->id)->whereBetween('started_at', [$from, $to])->sum('distance_meters') / 1000),
            'minutes' => (int) round(Trip::where('driver_id', $ridici->id)->whereBetween('started_at', [$from, $to])->sum('duration_seconds') / 60),
            'max_kmh' => (int) (Trip::where('driver_id', $ridici->id)->whereBetween('started_at', [$from, $to])->max('max_speed') ?? 0),
        ];

        return view('ridici.show', [
            'driver'    => $ridici,
            'trips'     => $trips,
            'stats'     => $stats,
            'fromInput' => $from->format('Y-m-d'),
            'toInput'   => $to->format('Y-m-d'),
        ]);
    }

    public function edit(Driver $ridici): View
    {
        return view('ridici.edit', ['driver' => $ridici]);
    }

    public function update(UpdateDriverRequest $request, Driver $ridici): RedirectResponse
    {
        $ridici->update($request->validated());

        return redirect()
            ->route('ridici.index')
            ->with('status', "Řidič {$ridici->full_name} byl upraven.");
    }

    public function destroy(Driver $ridici): RedirectResponse
    {
        $name = $ridici->full_name;
        $ridici->delete();

        return redirect()
            ->route('ridici.index')
            ->with('status', "Řidič {$name} byl smazán.");
    }
}
