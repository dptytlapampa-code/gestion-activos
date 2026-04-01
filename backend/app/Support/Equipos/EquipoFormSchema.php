<?php

namespace App\Support\Equipos;

use App\Models\Equipo;
use App\Services\ActiveInstitutionContext;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EquipoFormSchema
{
    public function __construct(
        private readonly ActiveInstitutionContext $activeInstitutionContext,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalize(array $input, string $prefix = ''): array
    {
        $officeKey = $this->key($prefix, 'office_id');
        $legacyOfficeKey = $this->key($prefix, 'oficina_id');

        Arr::set($input, $officeKey, Arr::get($input, $officeKey, Arr::get($input, $legacyOfficeKey)));

        foreach (['numero_serie', 'bien_patrimonial', 'mac_address'] as $field) {
            Arr::set(
                $input,
                $this->key($prefix, $field),
                $this->normalizeNullableText(Arr::get($input, $this->key($prefix, $field)))
            );
        }

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(mixed $user, array $input = [], ?Equipo $equipo = null, string $prefix = ''): array
    {
        $normalized = $this->normalize($input, $prefix);
        $institutionRule = Rule::exists('institutions', 'id');
        $scopeIds = $this->activeInstitutionContext->globalAdministrationScopeIds($user);

        if ($scopeIds !== null) {
            $institutionRule = $institutionRule->where(function ($query) use ($scopeIds): void {
                if ($scopeIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('id', $scopeIds);
            });
        }

        $numeroSerieRule = Rule::unique('equipos', 'numero_serie');
        $bienPatrimonialRule = Rule::unique('equipos', 'bien_patrimonial');

        if ($equipo instanceof Equipo) {
            $numeroSerieRule = $numeroSerieRule->ignore($equipo->id);
            $bienPatrimonialRule = $bienPatrimonialRule->ignore($equipo->id);
        }

        return [
            $this->key($prefix, 'institution_id') => [
                'required',
                'integer',
                $institutionRule,
            ],
            $this->key($prefix, 'service_id') => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(
                    fn ($query) => $query->where('institution_id', (int) Arr::get($normalized, $this->key($prefix, 'institution_id')))
                ),
            ],
            $this->key($prefix, 'office_id') => [
                'required',
                'integer',
                Rule::exists('offices', 'id')->where(
                    fn ($query) => $query->where('service_id', (int) Arr::get($normalized, $this->key($prefix, 'service_id')))
                ),
            ],
            $this->key($prefix, 'tipo_equipo_id') => ['required', 'integer', 'exists:tipos_equipos,id'],
            $this->key($prefix, 'marca') => ['required', 'string', 'max:100'],
            $this->key($prefix, 'modelo') => ['required', 'string', 'max:100'],
            $this->key($prefix, 'numero_serie') => ['nullable', 'string', 'max:120', $numeroSerieRule],
            $this->key($prefix, 'bien_patrimonial') => ['nullable', 'string', 'max:120', $bienPatrimonialRule],
            $this->key($prefix, 'mac_address') => ['nullable', 'string', 'max:50'],
            $this->key($prefix, 'estado') => ['required', Rule::in(Equipo::ESTADOS)],
            $this->key($prefix, 'fecha_ingreso') => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(string $prefix = ''): array
    {
        return [
            $this->key($prefix, 'institution_id').'.required' => 'Debe seleccionar una institucion.',
            $this->key($prefix, 'institution_id').'.exists' => 'La institucion seleccionada no es valida.',
            $this->key($prefix, 'service_id').'.required' => 'Debe seleccionar un servicio.',
            $this->key($prefix, 'service_id').'.exists' => 'El servicio seleccionado no corresponde a la institucion.',
            $this->key($prefix, 'office_id').'.required' => 'Debe seleccionar una oficina.',
            $this->key($prefix, 'office_id').'.exists' => 'La oficina seleccionada no corresponde al servicio.',
            $this->key($prefix, 'tipo_equipo_id').'.required' => 'Debe seleccionar un tipo de equipo.',
            $this->key($prefix, 'tipo_equipo_id').'.exists' => 'El tipo de equipo seleccionado no es valido.',
            $this->key($prefix, 'marca').'.required' => 'El campo marca es obligatorio.',
            $this->key($prefix, 'modelo').'.required' => 'El campo modelo es obligatorio.',
            $this->key($prefix, 'numero_serie').'.unique' => 'Ya existe un equipo con ese numero de serie.',
            $this->key($prefix, 'bien_patrimonial').'.unique' => 'Ya existe un equipo con ese bien patrimonial.',
            $this->key($prefix, 'mac_address').'.max' => 'La direccion MAC no puede superar los 50 caracteres.',
            $this->key($prefix, 'estado').'.required' => 'Debe seleccionar un estado.',
            $this->key($prefix, 'estado').'.in' => 'El estado seleccionado no es valido.',
            $this->key($prefix, 'fecha_ingreso').'.required' => 'La fecha de ingreso es obligatoria.',
            $this->key($prefix, 'fecha_ingreso').'.date' => 'La fecha de ingreso debe tener un formato valido.',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function applyBusinessRules(Validator $validator, array $input, ?Equipo $equipo = null, string $prefix = ''): void
    {
        $estado = (string) Arr::get($input, $this->key($prefix, 'estado'));

        if (! $equipo instanceof Equipo) {
            if ($estado === Equipo::ESTADO_MANTENIMIENTO) {
                $validator->errors()->add(
                    $this->key($prefix, 'estado'),
                    'El estado Mantenimiento se registra desde la ficha del equipo, dentro del historial tecnico de mantenimientos.'
                );
            }

            return;
        }

        $tieneMantenimientoAbierto = $equipo->tieneMantenimientoExternoAbierto();

        if ($estado === Equipo::ESTADO_MANTENIMIENTO && ! $tieneMantenimientoAbierto) {
            $validator->errors()->add(
                $this->key($prefix, 'estado'),
                'El equipo solo puede quedar en Mantenimiento si existe un mantenimiento externo abierto registrado desde su ficha.'
            );
        }

        if ($tieneMantenimientoAbierto && $estado !== Equipo::ESTADO_MANTENIMIENTO) {
            $validator->errors()->add(
                $this->key($prefix, 'estado'),
                'No puede cambiar manualmente el estado mientras exista un mantenimiento externo abierto. Registre el alta o la baja desde la ficha del equipo.'
            );
        }
    }

    private function key(string $prefix, string $field): string
    {
        return $prefix !== '' ? $prefix.'.'.$field : $field;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
