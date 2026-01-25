<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->route('institution_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('institutions', 'name')->ignore($institutionId),
            ],
            'code' => ['nullable', 'string', 'max:255'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.id' => ['nullable', 'integer', 'exists:services,id'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.offices' => ['required', 'array', 'min:1'],
            'services.*.offices.*.id' => ['nullable', 'integer', 'exists:offices,id'],
            'services.*.offices.*.name' => ['required', 'string', 'max:255'],
            'services.*.offices.*.floor' => ['nullable', 'string', 'max:255'],
        ];
    }
}
