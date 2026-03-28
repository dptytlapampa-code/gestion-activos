<?php

namespace App\Http\Requests;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Office;
use App\Models\Service;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SearchActaEquiposRequest extends FormRequest
{
    private string $authorizationMessage = 'No tiene permisos para buscar equipos para generar actas.';

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            $this->authorizationMessage = 'Debe iniciar sesion para buscar equipos para generar actas.';

            return false;
        }

        $response = Gate::forUser($user)->inspect('create', Acta::class);
        $this->authorizationMessage = $response->message() ?: $this->authorizationMessage;

        return $response->allowed();
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException($this->authorizationMessage);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->filled('q') ? trim((string) $this->input('q')) : null,
            'institution_id' => $this->filled('institution_id') ? $this->input('institution_id') : null,
            'service_id' => $this->filled('service_id') ? $this->input('service_id') : null,
            'office_id' => $this->filled('office_id') ? $this->input('office_id') : null,
            'tipo_equipo_id' => $this->filled('tipo_equipo_id') ? $this->input('tipo_equipo_id') : null,
            'estado' => $this->filled('estado') ? $this->input('estado') : null,
            'page' => $this->filled('page') ? $this->input('page') : 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'tipo_equipo_id' => ['nullable', 'integer', 'exists:tipo_equipos,id'],
            'estado' => ['nullable', Rule::in(Equipo::ESTADOS)],
            'page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $institutionId = $this->integer('institution_id');
            $serviceId = $this->integer('service_id');
            $officeId = $this->integer('office_id');

            if ($serviceId > 0 && $institutionId <= 0) {
                $validator->errors()->add('institution_id', 'Seleccione primero la institucion para filtrar por servicio.');
            }

            if ($officeId > 0 && $serviceId <= 0) {
                $validator->errors()->add('service_id', 'Seleccione primero el servicio para filtrar por oficina.');
            }

            if ($institutionId > 0 && $serviceId > 0) {
                $serviceBelongsToInstitution = Service::query()
                    ->where('id', $serviceId)
                    ->where('institution_id', $institutionId)
                    ->exists();

                if (! $serviceBelongsToInstitution) {
                    $validator->errors()->add('service_id', 'El servicio seleccionado no pertenece a la institucion elegida.');
                }
            }

            if ($serviceId > 0 && $officeId > 0) {
                $officeBelongsToService = Office::query()
                    ->where('id', $officeId)
                    ->where('service_id', $serviceId)
                    ->exists();

                if (! $officeBelongsToService) {
                    $validator->errors()->add('office_id', 'La oficina seleccionada no pertenece al servicio elegido.');
                }
            }
        });
    }
}
