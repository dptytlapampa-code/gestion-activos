<?php

namespace App\Http\Requests;

use App\Models\Mantenimiento;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Mantenimiento|null $mantenimiento */
        $mantenimiento = $this->route('mantenimiento');

        return $mantenimiento !== null
            && $this->user() !== null
            && $this->user()->can('update', $mantenimiento);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'titulo' => ['required', 'string', 'max:150'],
            'detalle' => ['required', 'string'],
            'proveedor' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Debe indicar la fecha del registro tecnico.',
            'fecha.date' => 'La fecha del registro tecnico no es valida.',
            'titulo.required' => 'Debe ingresar un titulo para la nota tecnica.',
            'titulo.max' => 'El titulo no puede superar los 150 caracteres.',
            'detalle.required' => 'Debe describir la intervencion tecnica.',
            'proveedor.max' => 'El nombre del proveedor no puede superar los 150 caracteres.',
        ];
    }
}
