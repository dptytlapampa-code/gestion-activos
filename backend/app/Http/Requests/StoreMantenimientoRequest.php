<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Equipo|null $equipo */
        $equipo = $this->route('equipo');

        $user = $this->user();

        if ($equipo === null || $user === null) {
            return false;
        }

        if (! $user->can('create', Mantenimiento::class) || ! $user->can('view', $equipo)) {
            return false;
        }

        return app(ActiveInstitutionContext::class)->isWithinGlobalAdministrationScope(
            $user,
            $equipo->oficina?->service?->institution_id
        );
    }

    public function rules(): array
    {
        $tipo = (string) $this->input('tipo');

        return [
            'fecha' => ['required', 'date'],
            'tipo' => ['required', Rule::in(Mantenimiento::TIPOS_MANUALES)],
            'titulo' => ['required', 'string', 'max:150'],
            'detalle' => ['required', 'string'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'fecha_ingreso_st' => [
                Rule::requiredIf($tipo === Mantenimiento::TIPO_EXTERNO),
                'nullable',
                'date',
            ],
            'fecha_egreso_st' => [
                Rule::requiredIf(in_array($tipo, Mantenimiento::TIPOS_CIERRE_EXTERNO, true)),
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Debe indicar la fecha del evento tecnico.',
            'fecha.date' => 'La fecha del evento tecnico no es valida.',
            'tipo.required' => 'Debe seleccionar el tipo de mantenimiento.',
            'tipo.in' => 'El tipo de mantenimiento seleccionado no es valido.',
            'titulo.required' => 'Debe ingresar un titulo para identificar el mantenimiento.',
            'titulo.max' => 'El titulo no puede superar los 150 caracteres.',
            'detalle.required' => 'Debe describir el mantenimiento realizado.',
            'proveedor.max' => 'El nombre del proveedor no puede superar los 150 caracteres.',
            'fecha_ingreso_st.required' => 'Debe informar la fecha de ingreso al servicio tecnico externo.',
            'fecha_ingreso_st.date' => 'La fecha de ingreso al servicio tecnico no es valida.',
            'fecha_egreso_st.required' => 'Debe informar la fecha de egreso para cerrar el mantenimiento externo.',
            'fecha_egreso_st.date' => 'La fecha de egreso del servicio tecnico no es valida.',
        ];
    }
}
