<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-system-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:120'],
            'primary_color' => ['required', 'string', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'sidebar_color' => ['required', 'string', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.regex' => 'El color ingresado no es valido.',
            'sidebar_color.regex' => 'El color ingresado no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'site_name' => 'nombre del sistema',
            'primary_color' => 'color primario',
            'sidebar_color' => 'color del sidebar',
            'logo' => 'logo institucional',
        ];
    }
}

