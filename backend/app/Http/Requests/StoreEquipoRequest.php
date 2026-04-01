<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use App\Models\Office;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use App\Support\Equipos\EquipoFormSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEquipoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->replace(app(EquipoFormSchema::class)->normalize($this->all()));
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
        return app(EquipoFormSchema::class)->rules($this->user(), $this->all());
    }

    public function messages(): array
    {
        return app(EquipoFormSchema::class)->messages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            app(EquipoFormSchema::class)->applyBusinessRules($validator, $this->all());
        });
    }
}

