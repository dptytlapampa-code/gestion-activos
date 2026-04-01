<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\Auditing\AuditLogService;
use Illuminate\Support\Facades\DB;

class EquipoRegistrationService
{
    public function __construct(
        private readonly EquipoStatusResolver $equipoStatusResolver,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @param  array{movement_observation?:string|null,audit_summary?:string|null}  $options
     */
    public function create(User $user, array $validated, array $options = []): Equipo
    {
        /** @var Equipo $equipo */
        $equipo = DB::transaction(function () use ($user, $validated, $options): Equipo {
            $tipoEquipo = TipoEquipo::query()->findOrFail((int) $validated['tipo_equipo_id']);

            $data = [
                'tipo_equipo_id' => (int) $validated['tipo_equipo_id'],
                'marca' => $validated['marca'],
                'modelo' => $validated['modelo'],
                'bien_patrimonial' => $validated['bien_patrimonial'] ?? null,
                'mac_address' => $validated['mac_address'] ?? null,
                'estado' => $validated['estado'],
                'equipo_status_id' => $this->equipoStatusResolver->resolveIdByEstado((string) $validated['estado'], 'estado'),
                'fecha_ingreso' => $validated['fecha_ingreso'],
                'oficina_id' => (int) $validated['office_id'],
                'tipo' => $tipoEquipo->nombre,
                'numero_serie' => $validated['numero_serie'] ?? null,
            ];

            $equipo = Equipo::query()->create($data);

            $oficinaDestino = Office::query()
                ->with('service.institution')
                ->find($equipo->oficina_id);

            $ubicacionDestino = $this->mapOfficeLocation($oficinaDestino);

            Movimiento::query()->create([
                'equipo_id' => $equipo->id,
                'user_id' => $user->id,
                'tipo_movimiento' => 'ingreso',
                'fecha' => now(),
                'institucion_destino_id' => $ubicacionDestino['institucion_id'],
                'servicio_destino_id' => $ubicacionDestino['servicio_id'],
                'oficina_destino_id' => $ubicacionDestino['oficina_id'],
                'observacion' => $this->nullableString($options['movement_observation'] ?? null) ?? 'Ingreso de equipo',
            ]);

            $snapshot = $this->equipmentAuditSnapshot($equipo, $oficinaDestino);

            $this->auditLogService->record([
                'user' => $user,
                'institution_id' => $ubicacionDestino['institucion_id'],
                'module' => 'equipos',
                'action' => 'equipo_creado',
                'entity_type' => 'equipo',
                'entity_id' => $equipo->id,
                'summary' => $this->nullableString($options['audit_summary'] ?? null)
                    ?? sprintf('Se dio de alta el equipo %s.', $this->equipmentReference($equipo)),
                'after' => $snapshot,
                'metadata' => [
                    'details' => $snapshot,
                ],
                'level' => AuditLog::LEVEL_CRITICAL,
                'is_critical' => true,
            ]);

            return $equipo;
        }, 3);

        return $equipo->loadMissing(['tipoEquipo:id,nombre', 'oficina.service.institution']);
    }

    /**
     * @return array{institucion_id:int|null,servicio_id:int|null,oficina_id:int|null}
     */
    private function mapOfficeLocation(?Office $office): array
    {
        return [
            'institucion_id' => $office?->service?->institution?->id,
            'servicio_id' => $office?->service?->id,
            'oficina_id' => $office?->id,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function equipmentAuditSnapshot(Equipo $equipo, ?Office $office): array
    {
        return [
            'tipo_equipo' => $equipo->tipo,
            'marca' => $equipo->marca ?: 'Sin marca',
            'modelo' => $equipo->modelo ?: 'Sin modelo',
            'numero_serie' => $equipo->numero_serie ?: 'Sin numero de serie',
            'bien_patrimonial' => $equipo->bien_patrimonial ?: 'Sin bien patrimonial',
            'codigo_interno' => $equipo->codigo_interno ?: 'Sin codigo interno',
            'estado' => $this->estadoLabel($equipo->estado),
            'institucion' => $office?->service?->institution?->nombre ?? 'Sin institucion',
            'servicio' => $office?->service?->nombre ?? 'Sin servicio',
            'oficina' => $office?->nombre ?? 'Sin oficina',
            'fecha_ingreso' => $equipo->fecha_ingreso?->format('d/m/Y') ?? 'Sin fecha de ingreso',
        ];
    }

    private function equipmentReference(Equipo $equipo): string
    {
        return $equipo->reference();
    }

    private function estadoLabel(?string $estado): string
    {
        return match ((string) $estado) {
            Equipo::ESTADO_OPERATIVO => 'Operativo',
            Equipo::ESTADO_PRESTADO => 'Prestado',
            Equipo::ESTADO_EN_MANTENIMIENTO, Equipo::ESTADO_MANTENIMIENTO => 'Mantenimiento',
            Equipo::ESTADO_FUERA_DE_SERVICIO => 'Fuera de servicio',
            Equipo::ESTADO_BAJA => 'Baja',
            default => ucfirst(str_replace('_', ' ', (string) $estado)),
        };
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
