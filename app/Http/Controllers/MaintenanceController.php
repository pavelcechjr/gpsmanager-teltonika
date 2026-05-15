<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $maintenances = Maintenance::query()
            ->with('vehicle:id,name,plate,brand,fuel_type')
            ->when($request->integer('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->string('status')->toString() === 'planned', fn ($q) => $q->whereNull('performed_at'))
            ->when($request->string('status')->toString() === 'done', fn ($q) => $q->whereNotNull('performed_at'))
            ->orderByRaw('COALESCE(performed_at, planned_at) DESC NULLS LAST')
            ->paginate(25)
            ->withQueryString();

        $vehicles = Vehicle::where('active', true)->orderBy('name')->get(['id', 'name', 'plate']);

        return view('udrzba.index', compact('maintenances', 'vehicles'));
    }

    public function create(): View
    {
        return view('udrzba.create', [
            'vehicles' => $this->vehicleOptions(),
            'types'    => Maintenance::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Maintenance::create($this->validateData($request));
        return redirect()->route('udrzba.index')->with('status', 'Záznam údržby přidán.');
    }

    public function edit(Maintenance $udrzba): View
    {
        return view('udrzba.edit', [
            'maintenance' => $udrzba,
            'vehicles'    => $this->vehicleOptions(),
            'types'       => Maintenance::TYPES,
        ]);
    }

    public function update(Request $request, Maintenance $udrzba): RedirectResponse
    {
        $udrzba->update($this->validateData($request));
        return redirect()->route('udrzba.index')->with('status', 'Záznam upraven.');
    }

    public function destroy(Maintenance $udrzba): RedirectResponse
    {
        $udrzba->delete();
        return redirect()->route('udrzba.index')->with('status', 'Záznam smazán.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'vehicle_id'   => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'type'         => ['required', 'string', Rule::in(array_keys(Maintenance::TYPES))],
            'planned_at'   => ['nullable', 'date'],
            'performed_at' => ['nullable', 'date'],
            'mileage_km'   => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'price'        => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'supplier'     => ['nullable', 'string', 'max:120'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ]);
    }

    protected function vehicleOptions(): array
    {
        return Vehicle::where('active', true)->orderBy('name')->get(['id', 'name', 'plate'])
            ->mapWithKeys(fn ($v) => [$v->id => "{$v->name} ({$v->plate})"])->all();
    }
}
