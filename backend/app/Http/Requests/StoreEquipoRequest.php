<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        return $office !== null
            && $office->service !== null
            && (int) $office->service->institution_id === (int) $user->institution_id;
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
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
            'estado' => ['required', Rule::in(Equipo::ESTADOS)],
            'fecha_ingreso' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'Debe seleccionar una institución.',
            'institution_id.exists' => 'La institución seleccionada no es válida.',
            'service_id.required' => 'Debe seleccionar un servicio.',
            'service_id.exists' => 'El servicio seleccionado no corresponde a la institución.',
            'office_id.required' => 'Debe seleccionar una oficina.',
            'office_id.exists' => 'La oficina seleccionada no corresponde al servicio.',
            'tipo_equipo_id.required' => 'Debe seleccionar un tipo de equipo.',
            'tipo_equipo_id.exists' => 'El tipo de equipo seleccionado no es válido.',
            'marca.required' => 'El campo marca es obligatorio.',
            'modelo.required' => 'El campo modelo es obligatorio.',
            'numero_serie.required' => 'El número de serie es obligatorio.',
            'numero_serie.unique' => 'Ya existe un equipo con ese número de serie.',
            'bien_patrimonial.required' => 'El bien patrimonial es obligatorio.',
            'bien_patrimonial.unique' => 'Ya existe un equipo con ese bien patrimonial.',
            'estado.required' => 'Debe seleccionar un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date' => 'La fecha de ingreso debe tener un formato válido.',
        ];
    }
}
