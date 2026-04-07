<?php

namespace App\Http\Requests;

use App\Models\RecepcionTecnica;
use App\Models\Equipo;
use App\Services\RecepcionTecnicaService;
use App\Support\Equipos\EquipoFormSchema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRecepcionTecnicaRequest extends FormRequest
{
    private string $authorizationMessage = 'No tiene permisos para registrar ingresos tecnicos.';

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        $input = app(EquipoFormSchema::class)->normalize($input);

        if (! array_key_exists('fecha_hora_ingreso', $input) && filled($input['fecha_recepcion'] ?? null)) {
            $input['fecha_hora_ingreso'] = trim((string) $input['fecha_recepcion']).'T'.now()->format('H:i');
        }

        $this->replace(array_merge($input, [
            'modo_equipo' => $this->input('modo_equipo', RecepcionTecnicaService::MODO_EQUIPO_NUEVO),
            'incorporar_equipo' => $this->boolean('incorporar_equipo'),
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
            'modo_equipo' => ['required', Rule::in([
                RecepcionTecnicaService::MODO_EQUIPO_EXISTENTE,
                RecepcionTecnicaService::MODO_EQUIPO_NUEVO,
            ])],
            'fecha_hora_ingreso' => ['required', 'date'],
            'sector_receptor' => ['nullable', 'string', 'max:120'],
            'equipo_id' => ['nullable', 'integer', 'exists:equipos,id'],
            'referencia_equipo' => ['nullable', 'string', 'max:255'],
            'tipo_equipo_texto' => ['nullable', 'string', 'max:120'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'bien_patrimonial' => ['nullable', 'string', 'max:120'],
            'procedencia_institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'procedencia_service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(
                    fn ($query) => $query->where('institution_id', $this->integer('procedencia_institution_id'))
                ),
            ],
            'procedencia_office_id' => [
                'nullable',
                'integer',
                Rule::exists('offices', 'id')->where(
                    fn ($query) => $query->where('service_id', $this->integer('procedencia_service_id'))
                ),
            ],
            'procedencia_hospital' => ['nullable', 'string', 'max:150'],
            'procedencia_libre' => ['nullable', 'string', 'max:255'],
            'persona_nombre' => ['required', 'string', 'max:150'],
            'persona_documento' => ['nullable', 'string', 'max:50'],
            'persona_telefono' => ['nullable', 'string', 'max:50'],
            'persona_area' => ['nullable', 'string', 'max:150'],
            'persona_institucion' => ['nullable', 'string', 'max:150'],
            'persona_relacion_equipo' => ['nullable', 'string', 'max:80'],
            'falla_motivo' => ['required', 'string', 'max:255'],
            'descripcion_falla' => ['nullable', 'string'],
            'accesorios_entregados' => ['nullable', 'string'],
            'estado_fisico_inicial' => ['nullable', 'string'],
            'observaciones_recepcion' => ['nullable', 'string'],
            'observaciones_internas' => ['nullable', 'string'],
            'incorporar_equipo' => ['nullable', 'boolean'],
        ];

        if ($this->boolean('incorporar_equipo') && $this->input('modo_equipo') === RecepcionTecnicaService::MODO_EQUIPO_NUEVO) {
            $rules = array_merge(
                $rules,
                app(EquipoFormSchema::class)->rules($this->user(), $this->all())
            );
        } else {
            $rules = array_merge($rules, [
                'institution_id' => ['nullable', 'integer'],
                'service_id' => ['nullable', 'integer'],
                'office_id' => ['nullable', 'integer'],
                'tipo_equipo_id' => ['nullable', 'integer'],
                'estado' => ['nullable', 'string'],
                'fecha_ingreso' => ['nullable', 'date'],
                'mac_address' => ['nullable', 'string', 'max:50'],
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(
            [
                'modo_equipo.required' => 'Debe indicar si trabajara con un equipo existente o con un equipo nuevo.',
                'modo_equipo.in' => 'La opcion seleccionada para el equipo no es valida.',
                'fecha_hora_ingreso.required' => 'Debe indicar la fecha y hora del ingreso tecnico.',
                'fecha_hora_ingreso.date' => 'La fecha y hora del ingreso tecnico deben tener un formato valido.',
                'equipo_id.exists' => 'El equipo seleccionado ya no esta disponible.',
                'persona_nombre.required' => 'Debe indicar quien entrega el equipo.',
                'falla_motivo.required' => 'Debe indicar el motivo principal del ingreso tecnico.',
                'procedencia_service_id.exists' => 'El servicio de procedencia no corresponde a la institucion seleccionada.',
                'procedencia_office_id.exists' => 'La oficina de procedencia no corresponde al servicio seleccionado.',
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
            $mode = (string) $this->input('modo_equipo');

            if ($mode === RecepcionTecnicaService::MODO_EQUIPO_EXISTENTE && ! $this->filled('equipo_id')) {
                $validator->errors()->add('equipo_id', 'Debe seleccionar un equipo existente para continuar.');
            }

            if ($mode === RecepcionTecnicaService::MODO_EQUIPO_EXISTENTE && $this->filled('equipo_id')) {
                $equipo = Equipo::query()->find((int) $this->input('equipo_id'));

                if ($equipo instanceof Equipo && $equipo->isBaja()) {
                    $validator->errors()->add('equipo_id', 'Este equipo esta en baja y no admite nuevos ingresos tecnicos.');
                }

                $hasOpenReception = RecepcionTecnica::query()
                    ->open()
                    ->where('equipo_id', (int) $this->input('equipo_id'))
                    ->exists();

                if ($hasOpenReception) {
                    $validator->errors()->add('equipo_id', 'Este equipo ya tiene un ingreso tecnico abierto.');
                }
            }

            if ($mode === RecepcionTecnicaService::MODO_EQUIPO_EXISTENTE && $this->boolean('incorporar_equipo')) {
                $validator->errors()->add('incorporar_equipo', 'La incorporacion al sistema solo aplica cuando el equipo todavia no esta registrado.');
            }

            if ($mode === RecepcionTecnicaService::MODO_EQUIPO_NUEVO) {
                $snapshot = collect([
                    $this->input('referencia_equipo'),
                    $this->input('tipo_equipo_texto'),
                    $this->input('marca'),
                    $this->input('modelo'),
                    $this->input('numero_serie'),
                    $this->input('bien_patrimonial'),
                ])->map(fn (mixed $value): string => trim((string) $value))->filter();

                if ($snapshot->isEmpty()) {
                    $validator->errors()->add(
                        'referencia_equipo',
                        'Debe cargar al menos una referencia visible del equipo para registrar el ingreso tecnico.'
                    );
                }
            }

            if ($this->boolean('incorporar_equipo') && $mode === RecepcionTecnicaService::MODO_EQUIPO_NUEVO) {
                app(EquipoFormSchema::class)->applyBusinessRules($validator, $this->all());
            }

            if (
                $this->filled('procedencia_office_id')
                && ! $this->filled('procedencia_service_id')
            ) {
                $validator->errors()->add('procedencia_service_id', 'Debe seleccionar el servicio de procedencia antes de elegir una oficina.');
            }

            if (
                $this->filled('procedencia_service_id')
                && ! $this->filled('procedencia_institution_id')
            ) {
                $validator->errors()->add('procedencia_institution_id', 'Debe seleccionar la institucion de procedencia antes de elegir un servicio.');
            }
        });
    }
}
