<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'imei'         => preg_replace('/\s+/', '', (string) $this->input('imei')),
            'phone_number' => $this->input('phone_number') ?: null,
            'model'        => $this->input('model') ?: null,
            'active'       => $this->boolean('active'),
        ]);
    }

    protected function deviceId(): ?int
    {
        $param = $this->route('zarizeni');
        if (is_object($param) && method_exists($param, 'getKey')) {
            return $param->getKey();
        }
        return is_numeric($param) ? (int) $param : null;
    }

    public function rules(): array
    {
        return [
            'imei'         => ['required', 'string', 'digits:15', Rule::unique('devices', 'imei')->ignore($this->deviceId())],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'model'        => ['nullable', 'string', 'max:100'],
            'active'       => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'imei'         => 'IMEI',
            'phone_number' => 'telefon',
            'model'        => 'model',
            'active'       => 'aktivní',
        ];
    }

    public function messages(): array
    {
        return [
            'imei.digits' => 'IMEI musí být přesně 15 číslic (Teltonika).',
            'imei.unique' => 'Zařízení s tímto IMEI již existuje.',
        ];
    }
}
