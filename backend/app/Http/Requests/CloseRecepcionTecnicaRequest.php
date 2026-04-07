<?php

namespace App\Http\Requests;

use App\Models\RecepcionTecnica;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CloseRecepcionTecnicaRequest extends FormRequest
{
    private string $authorizationMessage = 'No tiene permisos para cerrar este ingreso tecnico.';

    public function authorize(): bool
    {
        $user = $this->user();
        /** @var RecepcionTecnica|null $recepcionTecnica */
        $recepcionTecnica = $this->route('recepcionTecnica');

        if ($user === null || ! $recepcionTecnica instanceof RecepcionTecnica) {
            $this->authorizationMessage = 'Debe iniciar sesion para operar en Mesa Tecnica.';

            return false;
        }

        $response = Gate::forUser($user)->inspect('close', $recepcionTecnica);
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
            'estado_cierre' => ['required', Rule::in(RecepcionTecnica::ESTADOS_DE_CIERRE)],
            'fecha_entrega_real' => ['required', 'date'],
            'persona_retiro_nombre' => ['required', 'string', 'max:150'],
            'persona_retiro_documento' => ['nullable', 'string', 'max:50'],
            'persona_retiro_cargo' => ['nullable', 'string', 'max:150'],
            'diagnostico' => ['required', 'string'],
            'accion_realizada' => ['required', 'string'],
            'solucion_aplicada' => ['required', 'string'],
            'informe_tecnico' => ['required', 'string'],
            'observaciones_finales' => ['nullable', 'string'],
            'condicion_egreso' => ['required', Rule::in(RecepcionTecnica::CONDICIONES_EGRESO)],
            'institution_destino_id' => ['nullable'],
            'service_destino_id' => ['nullable'],
            'office_destino_id' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado_cierre.required' => 'Debe indicar el resultado del cierre tecnico.',
            'estado_cierre.in' => 'El resultado seleccionado no es valido para cerrar el ingreso tecnico.',
            'fecha_entrega_real.required' => 'Debe registrar la fecha y hora reales de entrega.',
            'fecha_entrega_real.date' => 'La fecha y hora de entrega deben tener un formato valido.',
            'persona_retiro_nombre.required' => 'Debe indicar quien retiro el equipo.',
            'diagnostico.required' => 'Debe registrar el diagnostico final.',
            'accion_realizada.required' => 'Debe registrar la accion realizada.',
            'solucion_aplicada.required' => 'Debe registrar la solucion aplicada.',
            'informe_tecnico.required' => 'Debe registrar el informe tecnico breve.',
            'condicion_egreso.required' => 'Debe indicar la condicion de egreso.',
            'condicion_egreso.in' => 'La condicion de egreso seleccionada no es valida.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if (
                    $this->input('estado_cierre') === RecepcionTecnica::ESTADO_NO_REPARABLE
                    && $this->input('condicion_egreso') !== RecepcionTecnica::CONDICION_NO_REPARABLE
                ) {
                    $validator->errors()->add(
                        'condicion_egreso',
                        'Si el cierre queda como no reparable, la condicion de egreso debe coincidir.'
                    );
                }

                if ($this->filled('institution_destino_id') || $this->filled('service_destino_id') || $this->filled('office_destino_id')) {
                    $validator->errors()->add(
                        'estado_cierre',
                        'El cierre tecnico no cambia la ubicacion patrimonial. Si el equipo debe quedar en otro destino, use Actas.'
                    );
                }
            },
        ];
    }
}
