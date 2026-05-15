<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'  => $this->input('email') ?: null,
            'phone'  => $this->input('phone') ?: null,
            'note'   => $this->input('note') ?: null,
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:200'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'note'       => ['nullable', 'string', 'max:1000'],
            'active'     => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'jméno',
            'last_name'  => 'příjmení',
            'email'      => 'email',
            'phone'      => 'telefon',
            'note'       => 'poznámka',
            'active'     => 'aktivní',
        ];
    }
}
