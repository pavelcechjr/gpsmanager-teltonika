<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Vehicle;
use App\Models\VehicleGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    // ─── Vehicle groups ────────────────────────────────────────────────

    public function vehicleIndex(): View
    {
        $groups = VehicleGroup::query()->withCount('vehicles')->orderBy('name')->get();
        return view('skupiny.vehicle-index', compact('groups'));
    }

    public function vehicleCreate(): View
    {
        return view('skupiny.vehicle-form', [
            'group'    => null,
            'vehicles' => Vehicle::where('active', true)->orderBy('name')->get(['id', 'name', 'plate']),
            'selected' => [],
        ]);
    }

    public function vehicleStore(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);
        $group = VehicleGroup::create($data);
        $group->vehicles()->sync($request->input('vehicles', []));
        return redirect()->route('vozidla.skupiny')->with('status', 'Skupina vytvořena.');
    }

    public function vehicleEdit(VehicleGroup $group): View
    {
        return view('skupiny.vehicle-form', [
            'group'    => $group,
            'vehicles' => Vehicle::where('active', true)->orderBy('name')->get(['id', 'name', 'plate']),
            'selected' => $group->vehicles->pluck('id')->all(),
        ]);
    }

    public function vehicleUpdate(Request $request, VehicleGroup $group): RedirectResponse
    {
        $group->update($this->validateGroup($request));
        $group->vehicles()->sync($request->input('vehicles', []));
        return redirect()->route('vozidla.skupiny')->with('status', 'Skupina upravena.');
    }

    public function vehicleDestroy(VehicleGroup $group): RedirectResponse
    {
        $group->delete();
        return redirect()->route('vozidla.skupiny')->with('status', 'Skupina smazána.');
    }

    // ─── Device groups ─────────────────────────────────────────────────

    public function deviceIndex(): View
    {
        $groups = DeviceGroup::query()->withCount('devices')->orderBy('name')->get();
        return view('skupiny.device-index', compact('groups'));
    }

    public function deviceCreate(): View
    {
        return view('skupiny.device-form', [
            'group'   => null,
            'devices' => Device::where('active', true)->orderBy('imei')->get(['id', 'imei', 'model']),
            'selected' => [],
        ]);
    }

    public function deviceStore(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);
        $group = DeviceGroup::create($data);
        $group->devices()->sync($request->input('devices', []));
        return redirect()->route('zarizeni.skupiny')->with('status', 'Skupina vytvořena.');
    }

    public function deviceEdit(DeviceGroup $group): View
    {
        return view('skupiny.device-form', [
            'group'    => $group,
            'devices'  => Device::where('active', true)->orderBy('imei')->get(['id', 'imei', 'model']),
            'selected' => $group->devices->pluck('id')->all(),
        ]);
    }

    public function deviceUpdate(Request $request, DeviceGroup $group): RedirectResponse
    {
        $group->update($this->validateGroup($request));
        $group->devices()->sync($request->input('devices', []));
        return redirect()->route('zarizeni.skupiny')->with('status', 'Skupina upravena.');
    }

    public function deviceDestroy(DeviceGroup $group): RedirectResponse
    {
        $group->delete();
        return redirect()->route('zarizeni.skupiny')->with('status', 'Skupina smazána.');
    }

    protected function validateGroup(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
