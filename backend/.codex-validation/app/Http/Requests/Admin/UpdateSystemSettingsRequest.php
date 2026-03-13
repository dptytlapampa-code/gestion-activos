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
            'logo_institucional' => ['nullable', 'file', 'mimes:png', 'max:3072'],
            'logo_pdf' => ['nullable', 'file', 'mimes:png', 'max:3072'],
            'logo' => ['nullable', 'file', 'mimes:png', 'max:3072'],
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.regex' => 'El color ingresado no es valido.',
            'sidebar_color.regex' => 'El color ingresado no es valido.',
            'logo_institucional.mimes' => 'El logo institucional debe estar en formato PNG.',
            'logo_pdf.mimes' => 'El logo para PDFs debe estar en formato PNG.',
            'logo.mimes' => 'El logo institucional debe estar en formato PNG.',
        ];
    }

    public function attributes(): array
    {
        return [
            'site_name' => 'nombre del sistema',
            'primary_color' => 'color primario',
            'sidebar_color' => 'color del sidebar',
            'logo_institucional' => 'logo institucional',
            'logo_pdf' => 'logo para PDF',
            'logo' => 'logo institucional',
        ];
    }
}
