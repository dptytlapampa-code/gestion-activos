<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use App\Models\Office;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use App\Support\Equipos\EquipoFormSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEquipoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->replace(app(EquipoFormSchema::class)->normalize($this->all()));
    }

    public function authorize(): bool
    {
        /** @var Equipo $equipo */
        $equipo = $this->route('equipo');

        $user = $this->user();

        if ($user === null || ! $user->can('update', $equipo)) {
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
        /** @var Equipo $equipo */
        $equipo = $this->route('equipo');
        return app(EquipoFormSchema::class)->rules($this->user(), $this->all(), $equipo);
    }

    public function messages(): array
    {
        return app(EquipoFormSchema::class)->messages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Equipo|null $equipo */
            $equipo = $this->route('equipo');

            if (! $equipo instanceof Equipo) {
                return;
            }

            app(EquipoFormSchema::class)->applyBusinessRules($validator, $this->all(), $equipo);
        });
    }
}

