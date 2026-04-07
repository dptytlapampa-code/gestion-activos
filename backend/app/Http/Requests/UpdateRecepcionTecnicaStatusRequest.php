<?php

namespace App\Http\Requests;

use App\Models\RecepcionTecnica;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRecepcionTecnicaStatusRequest extends FormRequest
{
    private string $authorizationMessage = 'No tiene permisos para actualizar este ingreso tecnico.';

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
            'estado' => ['required', Rule::in(RecepcionTecnica::ESTADOS_DE_SEGUIMIENTO)],
            'motivo_anulacion' => ['nullable', 'string'],
            'diagnostico' => ['nullable', 'string'],
            'accion_realizada' => ['nullable', 'string'],
            'solucion_aplicada' => ['nullable', 'string'],
            'informe_tecnico' => ['nullable', 'string'],
            'observaciones_internas' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe seleccionar el nuevo estado del ingreso tecnico.',
            'estado.in' => 'El estado seleccionado no es valido.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($this->input('estado') === RecepcionTecnica::ESTADO_CANCELADO && ! $this->filled('motivo_anulacion')) {
                    $validator->errors()->add('motivo_anulacion', 'Debe indicar el motivo de cancelacion.');
                }
            },
        ];
    }
}
