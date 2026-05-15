<?php

namespace App\Http\Controllers;

use App\Models\OdometerCalibration;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalibrationController extends Controller
{
    public function create(Vehicle $vozidla): View
    {
        return view('kalibrace.form', [
            'vehicle'     => $vozidla,
            'calibration' => null,
            'estimated'   => $vozidla->current_odometer_km,
        ]);
    }

    public function store(Request $request, Vehicle $vozidla): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['vehicle_id'] = $vozidla->id;
        $data['user_id'] = $request->user()->id;
        OdometerCalibration::create($data);

        return redirect()->route('vozidla.show', $vozidla)
            ->with('status', "Kalibrace zapsána ({$data['delta_km']} km).");
    }

    public function edit(Vehicle $vozidla, OdometerCalibration $calibration): View
    {
        abort_unless($calibration->vehicle_id === $vozidla->id, 404);

        return view('kalibrace.form', [
            'vehicle'     => $vozidla,
            'calibration' => $calibration,
            'estimated'   => $vozidla->current_odometer_km,
        ]);
    }

    public function update(Request $request, Vehicle $vozidla, OdometerCalibration $calibration): RedirectResponse
    {
        abort_unless($calibration->vehicle_id === $vozidla->id, 404);
        $calibration->update($this->validateData($request));

        return redirect()->route('vozidla.show', $vozidla)->with('status', 'Kalibrace upravena.');
    }

    public function destroy(Vehicle $vozidla, OdometerCalibration $calibration): RedirectResponse
    {
        abort_unless($calibration->vehicle_id === $vozidla->id, 404);
        $calibration->delete();
        return redirect()->route('vozidla.show', $vozidla)->with('status', 'Kalibrace smazána.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'applied_at' => ['required', 'date'],
            'delta_km'   => ['required', 'integer', 'between:-1000000,1000000', 'not_in:0'],
            'note'       => ['nullable', 'string', 'max:500'],
        ], [
            'delta_km.not_in' => 'Korekce nemůže být 0.',
        ], [
            'applied_at' => 'datum kalibrace',
            'delta_km'   => 'korekce (km)',
            'note'       => 'poznámka',
        ]);
    }
}
