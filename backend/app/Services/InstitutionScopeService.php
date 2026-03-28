<?php

namespace App\Services;

use App\Models\Institution;

class InstitutionScopeService
{
    public const CENTRAL_CODE = 'NIVEL-CENTRAL';

    public const CENTRAL_NAME = 'Nivel Central';

    public function ensureCentralInstitution(): Institution
    {
        $institution = Institution::query()
            ->where('scope_type', Institution::SCOPE_GLOBAL)
            ->first()
            ?? Institution::query()
                ->where('codigo', self::CENTRAL_CODE)
                ->orWhereRaw('lower(nombre) = ?', [
                    function_exists('mb_strtolower')
                        ? mb_strtolower(self::CENTRAL_NAME)
                        : strtolower(self::CENTRAL_NAME),
                ])
                ->orderBy('id')
                ->first()
            ?? new Institution();

        $institution->fill([
            'codigo' => self::CENTRAL_CODE,
            'nombre' => self::CENTRAL_NAME,
            'descripcion' => 'Institucion madre para la administracion global del sistema.',
            'tipo' => Institution::TIPO_OTRO,
            'estado' => Institution::ESTADO_ACTIVO,
            'scope_type' => Institution::SCOPE_GLOBAL,
        ]);

        $institution->save();

        Institution::query()
            ->where('id', '!=', $institution->id)
            ->where('scope_type', Institution::SCOPE_GLOBAL)
            ->update(['scope_type' => Institution::SCOPE_INSTITUTIONAL]);

        return $institution->fresh();
    }

    public function centralInstitution(): ?Institution
    {
        return Institution::query()
            ->where('scope_type', Institution::SCOPE_GLOBAL)
            ->first();
    }

    public function centralInstitutionId(): ?int
    {
        return $this->centralInstitution()?->id;
    }

    public function isGlobalInstitution(?Institution $institution): bool
    {
        return $institution?->isGlobalScope() ?? false;
    }

    public function isGlobalInstitutionId(?int $institutionId): bool
    {
        if ($institutionId === null || $institutionId <= 0) {
            return false;
        }

        return Institution::query()
            ->whereKey($institutionId)
            ->where('scope_type', Institution::SCOPE_GLOBAL)
            ->exists();
    }
}
