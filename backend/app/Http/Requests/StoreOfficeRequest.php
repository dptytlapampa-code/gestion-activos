<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\ActiveInstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN);
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
                Rule::exists('services', 'id')->where(function ($query): void {
                    $query->where('institution_id', $this->integer('institution_id'));
                }),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('offices', 'nombre')->where('service_id', $this->integer('service_id')),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_id.required' => 'Debe seleccionar una institucion antes de elegir un servicio.',
            'institution_id.exists' => 'La institucion seleccionada no es valida.',
            'service_id.required' => 'Debe seleccionar un servicio.',
            'service_id.exists' => 'El servicio seleccionado no pertenece a la institucion indicada.',
            'nombre.required' => 'Debe ingresar el nombre de la oficina.',
            'nombre.max' => 'El nombre de la oficina no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una oficina con ese nombre en el servicio seleccionado.',
            'descripcion.max' => 'La descripcion no puede superar los 2000 caracteres.',
        ];
    }

    private function mustScopeToUserInstitution(): bool
    {
        return true;
    }
}
