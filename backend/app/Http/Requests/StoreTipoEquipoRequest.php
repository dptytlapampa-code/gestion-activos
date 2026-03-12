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
            'imagen_png' => ['nullable', 'file', 'mimes:png', 'mimetypes:image/png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo de equipo con ese nombre.',
            'imagen_png.mimes' => 'La imagen debe estar en formato PNG.',
            'imagen_png.mimetypes' => 'La imagen debe estar en formato PNG valido.',
            'imagen_png.max' => 'La imagen no puede superar los 2 MB.',
        ];
    }
}