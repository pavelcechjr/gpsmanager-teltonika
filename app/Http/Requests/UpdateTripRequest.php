<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'driver_id'  => $this->input('driver_id') ?: null,
            'note'       => $this->input('note') ?: null,
            'is_private' => $this->boolean('is_private'),
        ]);
    }

    public function rules(): array
    {
        return [
            'driver_id'  => ['nullable', 'integer', Rule::exists('drivers', 'id')->where('active', true)],
            'note'       => ['nullable', 'string', 'max:2000'],
            'is_private' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'driver_id'  => 'řidič',
            'note'       => 'poznámka',
            'is_private' => 'soukromá jízda',
        ];
    }
}
