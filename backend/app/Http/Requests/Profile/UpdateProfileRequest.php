<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Debe ingresar su nombre.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'Debe ingresar su correo electronico.',
            'email.email' => 'El correo electronico no tiene un formato valido.',
            'email.max' => 'El correo electronico no puede superar los 255 caracteres.',
            'email.unique' => 'Ya existe otro usuario registrado con ese correo electronico.',
        ];
    }
}
