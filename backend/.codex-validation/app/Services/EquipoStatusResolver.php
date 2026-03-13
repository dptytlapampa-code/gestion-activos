<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\EquipoStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class EquipoStatusResolver
{
    public function resolveIdByEstado(string $estado, string $errorKey = 'equipos'): int
    {
        return $this->resolveIdByCanonicalCode($this->canonicalCodeByEstado($estado), $errorKey);
    }

    public function resolveIdByCanonicalCode(string $canonicalCode, string $errorKey = 'equipos'): int
    {
        $status = $this->findByCanonicalCode($canonicalCode);

        if ($status !== null) {
            return (int) $status->id;
        }

        $canonicalCode = EquipoStatus::normalizeCode($canonicalCode);

        if (array_key_exists($canonicalCode, EquipoStatus::canonicalDefinitions())) {
            return $this->resolveOrCreateIdByCanonicalCode($canonicalCode);
        }

        throw ValidationException::withMessages([
            $errorKey => "No se encontro un estado de equipo valido para el codigo {$canonicalCode}. Contacte al administrador.",
        ]);
    }

    public function resolveOrCreateIdByCanonicalCode(string $canonicalCode): int
    {
        $status = $this->findByCanonicalCode($canonicalCode);

        if ($status !== null) {
            return (int) $status->id;
        }

        $canonicalCode = EquipoStatus::normalizeCode($canonicalCode);
        $defaults = EquipoStatus::canonicalDefinitions()[$canonicalCode] ?? [
            'code' => $canonicalCode,
            'name' => ucwords(strtolower(str_replace('_', ' ', $canonicalCode))),
            'color' => 'gray',
            'is_terminal' => false,
        ];

        $created = EquipoStatus::query()->firstOrCreate(['code' => $defaults['code']], $defaults);

        return (int) $created->id;
    }

    public function resolveOrCreateOperativaId(): int
    {
        return $this->resolveOrCreateIdByCanonicalCode(EquipoStatus::CODE_OPERATIVA);
    }

    public function canonicalCodeByEstado(string $estado): string
    {
        return match ($estado) {
            Equipo::ESTADO_PRESTADO => EquipoStatus::CODE_PRESTADO,
            Equipo::ESTADO_EN_MANTENIMIENTO, Equipo::ESTADO_MANTENIMIENTO => EquipoStatus::CODE_EN_SERVICIO_TECNICO,
            Equipo::ESTADO_FUERA_DE_SERVICIO => EquipoStatus::CODE_FUERA_DE_SERVICIO,
            Equipo::ESTADO_BAJA => EquipoStatus::CODE_BAJA,
            default => EquipoStatus::CODE_OPERATIVA,
        };
    }

    /**
     * @return array<int, string>
     */
    public function aliasesForCanonicalCode(string $canonicalCode): array
    {
        return EquipoStatus::aliasesForCanonicalCode($canonicalCode);
    }

    private function findByCanonicalCode(string $canonicalCode): ?EquipoStatus
    {
        $aliases = $this->aliasesForCanonicalCode($canonicalCode);

        if ($aliases === []) {
            return null;
        }

        $statuses = $this->statusesIndexedByNormalizedCode();

        foreach ($aliases as $candidate) {
            $normalized = EquipoStatus::normalizeCode($candidate);
            if ($statuses->has($normalized)) {
                /** @var EquipoStatus $status */
                $status = $statuses->get($normalized);

                return $status;
            }
        }

        return null;
    }

    /**
     * @return Collection<string, EquipoStatus>
     */
    private function statusesIndexedByNormalizedCode(): Collection
    {
        return EquipoStatus::query()
            ->get(['id', 'code', 'name', 'color', 'is_terminal'])
            ->keyBy(fn (EquipoStatus $status): string => EquipoStatus::normalizeCode((string) $status->code));
    }
}


