<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'              => $this->input('name') ?: null,
            'plate'             => $this->plateNormalize($this->input('plate')),
            'color'             => $this->input('color') ?: null,
            'brand'             => $this->input('brand') ?: null,
            'fuel_type'         => $this->input('fuel_type') ?: null,
            'default_driver_id'   => $this->input('default_driver_id') ?: null,
            'device_id'           => $this->input('device_id') ?: null,
            'note'                => $this->input('note') ?: null,
            'active'              => $this->boolean('active'),
            'odometer_km'         => ($this->input('odometer_km') ?? '') !== '' ? $this->input('odometer_km') : null,
            'odometer_updated_at' => $this->input('odometer_updated_at') ?: null,
            'fuel_tank_l'         => ($this->input('fuel_tank_l') ?? '') !== '' ? $this->input('fuel_tank_l') : null,
        ]);
    }

    protected function plateNormalize(mixed $value): ?string
    {
        if (!$value) return null;
        return strtoupper(preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    protected function vehicleId(): ?int
    {
        $param = $this->route('vozidla');
        if (is_object($param) && method_exists($param, 'getKey')) {
            return $param->getKey();
        }
        return is_numeric($param) ? (int) $param : null;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:100'],
            'plate'             => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate')->ignore($this->vehicleId())],
            'color'             => ['nullable', 'string', 'max:30'],
            'brand'             => ['nullable', 'string', 'max:32', Rule::in(array_keys(\App\Models\Vehicle::BRANDS))],
            'fuel_type'         => ['nullable', 'string', 'max:16', Rule::in(array_keys(\App\Models\Vehicle::FUEL_TYPES))],
            'default_driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')->where('active', true)],
            'device_id'         => ['nullable', 'integer', Rule::exists('devices', 'id')->where('active', true), Rule::unique('vehicles', 'device_id')->ignore($this->vehicleId())],
            'note'                => ['nullable', 'string', 'max:1000'],
            'active'              => ['boolean'],
            'odometer_km'         => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'odometer_updated_at' => ['nullable', 'date'],
            'fuel_tank_l'         => ['nullable', 'numeric', 'min:0', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'              => 'název',
            'plate'             => 'SPZ',
            'color'             => 'barva',
            'default_driver_id' => 'default řidič',
            'device_id'         => 'Teltonika jednotka',
            'note'              => 'poznámka',
            'active'            => 'aktivní',
        ];
    }

    public function messages(): array
    {
        return [
            'plate.unique'     => 'Vozidlo s touto SPZ již existuje.',
            'device_id.unique' => 'Tato Teltonika jednotka je již přiřazena k jinému vozidlu.',
        ];
    }
}
