<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $devices = Device::query()
            ->with('vehicle:id,name,plate,brand,fuel_type,device_id')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('imei', 'ilike', $term)
                        ->orWhere('phone_number', 'ilike', $term)
                        ->orWhere('model', 'ilike', $term);
                });
            })
            ->when($request->status === 'active',   fn ($q) => $q->where('active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('active', false))
            ->when($request->state === 'online',  fn ($q) => $q->where('last_seen_at', '>=', now()->subMinutes(5)))
            ->when($request->state === 'offline', fn ($q) => $q->where(function ($q) {
                $q->where('last_seen_at', '<', now()->subMinutes(5))->orWhereNull('last_seen_at');
            }))
            ->orderBy('imei')
            ->paginate(20)
            ->withQueryString();

        return view('zarizeni.index', compact('devices'));
    }

    public function create(): View
    {
        return view('zarizeni.create');
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $device = Device::create($request->validated());

        return redirect()
            ->route('zarizeni.index')
            ->with('status', "Zařízení {$device->imei} bylo přidáno.");
    }

    public function edit(Device $zarizeni): View
    {
        return view('zarizeni.edit', ['device' => $zarizeni]);
    }

    public function update(UpdateDeviceRequest $request, Device $zarizeni): RedirectResponse
    {
        $zarizeni->update($request->validated());

        return redirect()
            ->route('zarizeni.index')
            ->with('status', "Zařízení {$zarizeni->imei} bylo upraveno.");
    }

    public function destroy(Device $zarizeni): RedirectResponse
    {
        $imei = $zarizeni->imei;
        $zarizeni->delete();

        return redirect()
            ->route('zarizeni.index')
            ->with('status', "Zařízení {$imei} bylo smazáno.");
    }
}
