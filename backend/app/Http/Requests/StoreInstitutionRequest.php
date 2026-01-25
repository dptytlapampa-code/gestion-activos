<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class StoreInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:institutions,name'],
            'code' => ['nullable', 'string', 'max:255'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.offices' => ['required', 'array', 'min:1'],
            'services.*.offices.*.name' => ['required', 'string', 'max:255'],
            'services.*.offices.*.floor' => ['nullable', 'string', 'max:255'],
        ];
    }
}
