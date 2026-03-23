<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Debe ingresar su contrasena actual.',
            'current_password.current_password' => 'La contrasena actual no coincide con nuestros registros.',
            'password.required' => 'Debe ingresar una nueva contrasena.',
            'password.confirmed' => 'La confirmacion de la nueva contrasena no coincide.',
            'password.different' => 'La nueva contrasena debe ser distinta de la actual.',
        ];
    }
}
