<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefuelingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'driver_id'  => $this->input('driver_id') ?: null,
            'mileage_km' => $this->input('mileage_km') ?: null,
            'station'    => $this->input('station') ?: null,
            'note'       => $this->input('note') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'vehicle_id'  => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'driver_id'   => ['nullable', 'integer', Rule::exists('drivers', 'id')],
            'fueled_at'   => ['required', 'date'],
            'liters'      => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'price_total' => ['required', 'numeric', 'min:0', 'max:999999'],
            'mileage_km'  => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'fuel_type'   => ['required', 'string', 'max:30'],
            'station'     => ['nullable', 'string', 'max:120'],
            'note'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vehicle_id'  => 'vozidlo',
            'driver_id'   => 'řidič',
            'fueled_at'   => 'datum tankování',
            'liters'      => 'litry',
            'price_total' => 'celková cena',
            'mileage_km'  => 'kilometráž',
            'fuel_type'   => 'palivo',
            'station'     => 'stanice',
        ];
    }
}
