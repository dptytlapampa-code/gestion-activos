<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMesaTecnicaRecepcionRequest extends FormRequest
{
    private string $authorizationMessage = 'No tiene permisos para operar en Mesa Tecnica.';

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            $this->authorizationMessage = 'Debe iniciar sesion para operar en Mesa Tecnica.';

            return false;
        }

        $response = Gate::forUser($user)->inspect('mesa_tecnica_access');
        $this->authorizationMessage = $response->message() ?: $this->authorizationMessage;

        return $response->allowed();
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException($this->authorizationMessage);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Debe indicar la fecha de recepcion.',
            'equipo_id.required' => 'Debe seleccionar un equipo para continuar.',
            'equipo_id.exists' => 'El equipo seleccionado ya no esta disponible.',
        ];
    }
}
