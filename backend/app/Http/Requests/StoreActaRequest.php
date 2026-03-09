<?php

namespace App\Http\Requests;

use App\Models\Acta;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('create', Acta::class);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(Acta::TIPOS)],
            'fecha' => ['required', 'date'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'institution_destino_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'service_origen_id' => ['nullable', 'integer', 'exists:services,id'],
            'office_origen_id' => ['nullable', 'integer', 'exists:offices,id'],
            'service_destino_id' => ['nullable', 'integer', 'exists:services,id'],
            'office_destino_id' => ['nullable', 'integer', 'exists:offices,id'],
            'receptor_nombre' => ['nullable', 'string', 'max:255'],
            'receptor_dni' => ['nullable', 'string', 'max:50'],
            'receptor_cargo' => ['nullable', 'string', 'max:255'],
            'receptor_dependencia' => ['nullable', 'string', 'max:255'],
            'motivo_baja' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'equipos' => ['required', 'array', 'min:1'],
            'equipos.*.equipo_id' => ['required', 'integer', 'distinct', 'exists:equipos,id'],
            'equipos.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'equipos.*.accesorios' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $tipo = (string) $this->input('tipo');

            if (! $this->filled('institution_id')) {
                $validator->errors()->add('institution_id', 'Debe seleccionar la institucion.');
            }

            if (in_array($tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_PRESTAMO, Acta::TIPO_MANTENIMIENTO, Acta::TIPO_DEVOLUCION], true)) {
                if (! $this->filled('service_destino_id')) {
                    $validator->errors()->add('service_destino_id', 'Debe seleccionar el servicio.');
                }

                if (! $this->filled('office_destino_id')) {
                    $validator->errors()->add('office_destino_id', 'Debe seleccionar la oficina.');
                }
            }

            if ($tipo === Acta::TIPO_TRASLADO) {
                foreach (['institution_id', 'service_origen_id', 'office_origen_id', 'institution_destino_id', 'service_destino_id', 'office_destino_id'] as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, 'Este campo es obligatorio para el traslado.');
                    }
                }
            }

            if ($tipo === Acta::TIPO_BAJA && ! $this->filled('motivo_baja')) {
                $validator->errors()->add('motivo_baja', 'Debe indicar el motivo de baja.');
            }

            if (in_array($tipo, [Acta::TIPO_ENTREGA, Acta::TIPO_PRESTAMO], true)) {
                foreach (['receptor_nombre', 'receptor_dni'] as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, 'Este campo es obligatorio para este tipo de acta.');
                    }
                }
            }

            $this->validateLocationHierarchy($validator, $tipo);
        });
    }

    private function validateLocationHierarchy($validator, string $tipo): void
    {
        $institutionId = $this->integer('institution_id');
        $institutionDestinoId = $this->integer('institution_destino_id');
        $serviceOrigenId = $this->integer('service_origen_id');
        $serviceDestinoId = $this->integer('service_destino_id');
        $officeOrigenId = $this->integer('office_origen_id');
        $officeDestinoId = $this->integer('office_destino_id');

        if ($serviceOrigenId > 0 && $institutionId > 0) {
            $belongs = Service::query()->where('id', $serviceOrigenId)->where('institution_id', $institutionId)->exists();
            if (! $belongs) {
                $validator->errors()->add('service_origen_id', 'El servicio origen no pertenece a la institucion origen.');
            }
        }

        if ($officeOrigenId > 0 && $serviceOrigenId > 0) {
            $belongs = Office::query()->where('id', $officeOrigenId)->where('service_id', $serviceOrigenId)->exists();
            if (! $belongs) {
                $validator->errors()->add('office_origen_id', 'La oficina origen no pertenece al servicio origen.');
            }
        }

        $destInstitutionForService = $tipo === Acta::TIPO_TRASLADO ? $institutionDestinoId : $institutionId;

        if ($serviceDestinoId > 0 && $destInstitutionForService > 0) {
            $belongs = Service::query()->where('id', $serviceDestinoId)->where('institution_id', $destInstitutionForService)->exists();
            if (! $belongs) {
                $validator->errors()->add('service_destino_id', 'El servicio destino no pertenece a la institucion destino.');
            }
        }

        if ($officeDestinoId > 0 && $serviceDestinoId > 0) {
            $belongs = Office::query()->where('id', $officeDestinoId)->where('service_id', $serviceDestinoId)->exists();
            if (! $belongs) {
                $validator->errors()->add('office_destino_id', 'La oficina destino no pertenece al servicio destino.');
            }
        }

        $user = $this->user();

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN)) {
            if ($institutionId > 0 && $institutionId !== (int) $user->institution_id) {
                $validator->errors()->add('institution_id', 'No tiene permisos para operar sobre otra institucion.');
            }

            if ($institutionDestinoId > 0 && $institutionDestinoId !== (int) $user->institution_id) {
                $validator->errors()->add('institution_destino_id', 'No tiene permisos para operar sobre otra institucion.');
            }
        }
    }
}

