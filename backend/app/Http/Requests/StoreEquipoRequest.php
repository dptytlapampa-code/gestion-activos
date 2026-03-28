<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use App\Models\Office;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEquipoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'office_id' => $this->input('office_id', $this->input('oficina_id')),
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('create', Equipo::class)) {
            return false;
        }

        if (! $user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        $office = Office::query()->with('service')->find($this->integer('office_id'));

        if ($office === null || $office->service === null) {
            return false;
        }

        return app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope(
            $user,
            (int) $office->service->institution_id
        );
    }

    public function rules(): array
    {
        $institutionRule = Rule::exists('institutions', 'id');
        $scopeIds = app(ActiveInstitutionContext::class)->globalAdministrationScopeIds($this->user());

        if ($scopeIds !== null) {
            $institutionRule = $institutionRule->where(function ($query) use ($scopeIds): void {
                if ($scopeIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('id', $scopeIds);
            });
        }

        return [
            'institution_id' => [
                'required',
                'integer',
                $institutionRule,
            ],
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('institution_id', $this->integer('institution_id'))),
            ],
            'office_id' => [
                'required',
                'integer',
                Rule::exists('offices', 'id')->where(fn ($query) => $query->where('service_id', $this->integer('service_id'))),
            ],
            'tipo_equipo_id' => ['required', 'integer', 'exists:tipos_equipos,id'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'numero_serie' => ['required', 'string', 'max:120', 'unique:equipos,numero_serie'],
            'bien_patrimonial' => ['required', 'string', 'max:120', 'unique:equipos,bien_patrimonial'],
            'mac_address' => ['nullable', 'string', 'max:50'],
            'codigo_interno' => ['nullable', 'string', 'max:120'],
            'estado' => ['required', Rule::in(Equipo::ESTADOS)],
            'fecha_ingreso' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'Debe seleccionar una institucion.',
            'institution_id.exists' => 'La institucion seleccionada no es valida.',
            'service_id.required' => 'Debe seleccionar un servicio.',
            'service_id.exists' => 'El servicio seleccionado no corresponde a la institucion.',
            'office_id.required' => 'Debe seleccionar una oficina.',
            'office_id.exists' => 'La oficina seleccionada no corresponde al servicio.',
            'tipo_equipo_id.required' => 'Debe seleccionar un tipo de equipo.',
            'tipo_equipo_id.exists' => 'El tipo de equipo seleccionado no es valido.',
            'marca.required' => 'El campo marca es obligatorio.',
            'modelo.required' => 'El campo modelo es obligatorio.',
            'numero_serie.required' => 'El numero de serie es obligatorio.',
            'numero_serie.unique' => 'Ya existe un equipo con ese numero de serie.',
            'bien_patrimonial.required' => 'El bien patrimonial es obligatorio.',
            'bien_patrimonial.unique' => 'Ya existe un equipo con ese bien patrimonial.',
            'mac_address.max' => 'La direccion MAC no puede superar los 50 caracteres.',
            'codigo_interno.max' => 'El codigo interno no puede superar los 120 caracteres.',
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es valido.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date' => 'La fecha de ingreso debe tener un formato valido.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((string) $this->input('estado') === Equipo::ESTADO_MANTENIMIENTO) {
                $validator->errors()->add(
                    'estado',
                    'El estado Mantenimiento se registra desde la ficha del equipo, dentro del historial tecnico de mantenimientos.'
                );
            }
        });
    }
}

