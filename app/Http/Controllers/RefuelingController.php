<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRefuelingRequest;
use App\Http\Requests\UpdateRefuelingRequest;
use App\Models\Driver;
use App\Models\Refueling;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefuelingController extends Controller
{
    public function index(Request $request): View
    {
        $refuelings = Refueling::query()
            ->with(['vehicle:id,name,plate,brand,fuel_type', 'driver:id,first_name,last_name'])
            ->when($request->integer('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->orderByDesc('fueled_at')
            ->paginate(25)
            ->withQueryString();

        $totals = [
            'count'  => Refueling::count(),
            'liters' => (float) Refueling::sum('liters'),
            'total'  => (float) Refueling::sum('price_total'),
        ];

        $vehicles = Vehicle::where('active', true)->orderBy('name')->get(['id', 'name', 'plate']);

        return view('tankovani.index', compact('refuelings', 'vehicles', 'totals'));
    }

    public function create(): View
    {
        return view('tankovani.create', [
            'vehicles' => $this->vehicleOptions(),
            'drivers'  => $this->driverOptions(),
        ]);
    }

    public function store(StoreRefuelingRequest $request): RedirectResponse
    {
        $r = Refueling::create($request->validated());
        return redirect()->route('tankovani.index')->with('status', "Tankování {$r->liters} l přidáno.");
    }

    public function edit(Refueling $tankovani): View
    {
        return view('tankovani.edit', [
            'refueling' => $tankovani,
            'vehicles'  => $this->vehicleOptions(),
            'drivers'   => $this->driverOptions(),
        ]);
    }

    public function update(UpdateRefuelingRequest $request, Refueling $tankovani): RedirectResponse
    {
        $tankovani->update($request->validated());
        return redirect()->route('tankovani.index')->with('status', 'Tankování upraveno.');
    }

    public function destroy(Refueling $tankovani): RedirectResponse
    {
        $tankovani->delete();
        return redirect()->route('tankovani.index')->with('status', 'Tankování smazáno.');
    }

    protected function vehicleOptions(): array
    {
        return Vehicle::where('active', true)->orderBy('name')->get(['id', 'name', 'plate'])
            ->mapWithKeys(fn ($v) => [$v->id => "{$v->name} ({$v->plate})"])->all();
    }

    protected function driverOptions(): array
    {
        return Driver::where('active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($d) => [$d->id => trim($d->last_name . ' ' . $d->first_name)])->all();
    }
}
