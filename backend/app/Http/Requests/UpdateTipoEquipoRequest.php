<?php

namespace App\Http\Requests;

use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN);
    }

    public function rules(): array
    {
        /** @var TipoEquipo $tipo_equipo */
        $tipo_equipo = $this->route('tipo_equipo');

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tipos_equipos', 'nombre')->ignore($tipo_equipo->id),
            ],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo de equipo con ese nombre.',
        ];
    }
}
