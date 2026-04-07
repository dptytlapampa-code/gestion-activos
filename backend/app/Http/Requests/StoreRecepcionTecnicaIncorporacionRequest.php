<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use App\Models\RecepcionTecnica;
use App\Services\RecepcionTecnicaService;
use App\Support\Equipos\EquipoFormSchema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRecepcionTecnicaIncorporacionRequest extends FormRequest
{
    private string $authorizationMessage = 'No tiene permisos para incorporar equipos desde este ingreso tecnico.';

    protected function prepareForValidation(): void
    {
        $input = app(EquipoFormSchema::class)->normalize($this->all());

        $this->replace(array_merge($input, [
            'modo_incorporacion' => $this->input('modo_incorporacion', RecepcionTecnicaService::MODO_INCORPORACION_NUEVO),
        ]));
    }

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
        $rules = [
            'modo_incorporacion' => ['required', Rule::in([
                RecepcionTecnicaService::MODO_INCORPORACION_EXISTENTE,
                RecepcionTecnicaService::MODO_INCORPORACION_NUEVO,
            ])],
            'equipo_id' => ['nullable', 'integer', 'exists:equipos,id'],
        ];

        if ($this->input('modo_incorporacion') === RecepcionTecnicaService::MODO_INCORPORACION_NUEVO) {
            $rules = array_merge($rules, app(EquipoFormSchema::class)->rules($this->user(), $this->all()));
        }

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(
            [
                'modo_incorporacion.required' => 'Debe indicar si desea vincular un equipo existente o crear uno nuevo.',
                'modo_incorporacion.in' => 'La opcion de incorporacion seleccionada no es valida.',
                'equipo_id.exists' => 'El equipo seleccionado ya no esta disponible.',
            ],
            app(EquipoFormSchema::class)->messages(),
            [
                'numero_serie.unique' => 'Ya existe un equipo con ese numero de serie. Puede vincular el equipo existente o revisar los datos.',
                'bien_patrimonial.unique' => 'Ya existe un equipo con ese bien patrimonial. Puede vincular el equipo existente o revisar los datos.',
            ]
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $this->input('modo_incorporacion') === RecepcionTecnicaService::MODO_INCORPORACION_EXISTENTE
                && ! $this->filled('equipo_id')
            ) {
                $validator->errors()->add('equipo_id', 'Debe seleccionar un equipo existente para vincularlo.');
            }

            if (
                $this->input('modo_incorporacion') === RecepcionTecnicaService::MODO_INCORPORACION_EXISTENTE
                && $this->filled('equipo_id')
            ) {
                $equipo = Equipo::query()->find((int) $this->input('equipo_id'));
                /** @var RecepcionTecnica|null $recepcionTecnica */
                $recepcionTecnica = $this->route('recepcionTecnica');

                if ($equipo instanceof Equipo && $equipo->isBaja()) {
                    $validator->errors()->add('equipo_id', 'Este equipo esta en baja y no admite nuevos ingresos tecnicos.');
                }

                $hasOpenReception = RecepcionTecnica::query()
                    ->open()
                    ->where('equipo_id', (int) $this->input('equipo_id'))
                    ->when($recepcionTecnica instanceof RecepcionTecnica, fn ($query) => $query->where('id', '!=', $recepcionTecnica->id))
                    ->exists();

                if ($hasOpenReception) {
                    $validator->errors()->add('equipo_id', 'Este equipo ya tiene un ingreso tecnico abierto.');
                }
            }

            if ($this->input('modo_incorporacion') === RecepcionTecnicaService::MODO_INCORPORACION_NUEVO) {
                app(EquipoFormSchema::class)->applyBusinessRules($validator, $this->all());
            }
        });
    }
}
