<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreTipoEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:tipos_equipos,nombre'],
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
