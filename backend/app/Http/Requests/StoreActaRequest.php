<?php

namespace App\Http\Requests;

use App\Models\Acta;
use App\Models\Office;
use App\Models\Service;
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
            'service_origen_id' => ['prohibited'],
            'office_origen_id' => ['prohibited'],
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

            if ($tipo === Acta::TIPO_ENTREGA) {
                if (! $this->filled('institution_destino_id')) {
                    $validator->errors()->add('institution_destino_id', 'Debe seleccionar la institucion destino.');
                }

                if (! $this->filled('service_destino_id')) {
                    $validator->errors()->add('service_destino_id', 'Debe seleccionar el servicio destino.');
                }

                if (! $this->filled('office_destino_id')) {
                    $validator->errors()->add('office_destino_id', 'Debe seleccionar la oficina destino.');
                }

                if (! $this->filled('receptor_nombre')) {
                    $validator->errors()->add('receptor_nombre', 'Debe indicar el receptor para la entrega.');
                }
            }

            if ($tipo === Acta::TIPO_TRASLADO) {
                if ($this->filled('institution_destino_id')) {
                    $validator->errors()->add('institution_destino_id', 'El traslado no permite cambiar de institucion.');
                }

                if (! $this->filled('service_destino_id')) {
                    $validator->errors()->add('service_destino_id', 'Debe seleccionar el servicio destino.');
                }

                if (! $this->filled('office_destino_id')) {
                    $validator->errors()->add('office_destino_id', 'Debe seleccionar la oficina destino.');
                }
            }

            if ($tipo === Acta::TIPO_PRESTAMO && ! $this->filled('receptor_nombre')) {
                $validator->errors()->add('receptor_nombre', 'Debe indicar el receptor del prestamo.');
            }

            if ($tipo === Acta::TIPO_BAJA && ! $this->filled('motivo_baja')) {
                $validator->errors()->add('motivo_baja', 'Debe indicar el motivo de baja.');
            }

            $this->validateLocationHierarchy($validator, $tipo);
        });
    }

    private function validateLocationHierarchy($validator, string $tipo): void
    {
        $serviceDestinoId = $this->integer('service_destino_id');
        $officeDestinoId = $this->integer('office_destino_id');
        $institutionDestinoId = $this->integer('institution_destino_id');

        if ($tipo === Acta::TIPO_ENTREGA && $serviceDestinoId > 0 && $institutionDestinoId > 0) {
            $belongs = Service::query()->where('id', $serviceDestinoId)->where('institution_id', $institutionDestinoId)->exists();
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
    }
}
