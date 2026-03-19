<?php

namespace App\Http\Requests;

use App\Http\Requests\Exports\ExportListingRequest;
use App\Models\Equipo;
use Illuminate\Validation\Rule;

class ExportEquiposRequest extends ExportListingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export', Equipo::class) ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => ['nullable', 'string'],
            'tipo' => ['nullable', 'string'],
            'marca' => ['nullable', 'string'],
            'modelo' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', Rule::in(Equipo::ESTADOS)],
        ]);
    }
}
