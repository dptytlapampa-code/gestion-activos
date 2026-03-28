<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMesaTecnicaEntregaRequest extends FormRequest
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
            'institution_destino_id' => ['required', 'integer', 'exists:institutions,id'],
            'service_destino_id' => ['required', 'integer', 'exists:services,id'],
            'office_destino_id' => ['required', 'integer', 'exists:offices,id'],
            'receptor_nombre' => ['required', 'string', 'max:255'],
            'receptor_dni' => ['nullable', 'string', 'max:50'],
            'receptor_cargo' => ['nullable', 'string', 'max:255'],
            'receptor_dependencia' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Debe indicar la fecha de entrega.',
            'equipo_id.required' => 'Debe seleccionar un equipo para continuar.',
            'institution_destino_id.required' => 'Debe seleccionar la institucion destino.',
            'service_destino_id.required' => 'Debe seleccionar el servicio destino.',
            'office_destino_id.required' => 'Debe seleccionar la oficina destino.',
            'receptor_nombre.required' => 'Debe indicar el nombre del receptor.',
        ];
    }
}
