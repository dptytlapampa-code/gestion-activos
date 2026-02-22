<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovimientoService
{
    public function registrar(Equipo $equipo, User $user, array $data): void
    {
        DB::transaction(function () use ($equipo, $user, $data): void {
            $equipo->refresh()->load('oficina.service.institution');
            $tipo = $data['tipo_movimiento'];

            if ($equipo->estado === Equipo::ESTADO_BAJA) {
                throw ValidationException::withMessages([
                    'equipo' => 'El equipo se encuentra en baja y no admite nuevos movimientos.',
                ]);
            }

            $ubicacionOrigen = $equipo->ubicacionActual();
            $ubicacionDestino = [
                'institucion_id' => null,
                'servicio_id' => null,
                'oficina_id' => null,
            ];

            $this->validarReglas($equipo, $user, $data, $ubicacionOrigen);

            if (in_array($tipo, [Movimiento::TIPO_TRASLADO, Movimiento::TIPO_TRANSFERENCIA_INTERNA, Movimiento::TIPO_TRANSFERENCIA_EXTERNA], true)) {
                $this->validarJerarquiaDestino(
                    (int) $data['institucion_destino_id'],
                    (int) $data['servicio_destino_id'],
                    (int) $data['oficina_destino_id'],
                );

                $ubicacionDestino = [
                    'institucion_id' => (int) $data['institucion_destino_id'],
                    'servicio_id' => (int) $data['servicio_destino_id'],
                    'oficina_id' => (int) $data['oficina_destino_id'],
                ];
            }

            $estadoNuevo = $equipo->estado;
            $prestamoData = [
                'receptor_nombre' => null,
                'receptor_dni' => null,
                'receptor_cargo' => null,
                'fecha_inicio_prestamo' => null,
                'fecha_estimada_devolucion' => null,
                'fecha_devolucion_real' => null,
            ];

            if ($tipo === Movimiento::TIPO_MANTENIMIENTO) {
                $estadoNuevo = Equipo::ESTADO_MANTENIMIENTO;
                $equipo->equipo_status_id = (int) EquipoStatus::query()->where('code', EquipoStatus::CODE_EN_SERVICIO_TECNICO)->value('id');
            }

            if ($tipo === Movimiento::TIPO_PRESTAMO) {
                $estadoNuevo = Equipo::ESTADO_PRESTAMO;
                $prestamoData = [
                    'receptor_nombre' => $data['receptor_nombre'],
                    'receptor_dni' => $data['receptor_dni'],
                    'receptor_cargo' => $data['receptor_cargo'] ?? null,
                    'fecha_inicio_prestamo' => $data['fecha_inicio_prestamo'],
                    'fecha_estimada_devolucion' => $data['fecha_estimada_devolucion'],
                    'fecha_devolucion_real' => null,
                ];
            }

            if ($tipo === Movimiento::TIPO_DEVOLUCION) {
                $prestamo = $this->prestamoActivo($equipo);

                $ubicacionDestino = [
                    'institucion_id' => $prestamo->institucion_origen_id,
                    'servicio_id' => $prestamo->servicio_origen_id,
                    'oficina_id' => $prestamo->oficina_origen_id,
                ];

                $prestamo->update(['fecha_devolucion_real' => now()]);
                $estadoNuevo = Equipo::ESTADO_OPERATIVO;
            }

            if ($tipo === Movimiento::TIPO_BAJA) {
                $equipo->equipo_status_id = (int) EquipoStatus::query()->where('code', EquipoStatus::CODE_BAJA)->value('id');
                $estadoNuevo = Equipo::ESTADO_BAJA;
            }

            Movimiento::query()->create([
                'equipo_id' => $equipo->id,
                'user_id' => $user->id,
                'tipo_movimiento' => $tipo,
                'fecha' => now(),
                'institucion_origen_id' => $ubicacionOrigen['institucion_id'],
                'servicio_origen_id' => $ubicacionOrigen['servicio_id'],
                'oficina_origen_id' => $ubicacionOrigen['oficina_id'],
                'institucion_destino_id' => $ubicacionDestino['institucion_id'],
                'servicio_destino_id' => $ubicacionDestino['servicio_id'],
                'oficina_destino_id' => $ubicacionDestino['oficina_id'],
                'receptor_nombre' => $prestamoData['receptor_nombre'],
                'receptor_dni' => $prestamoData['receptor_dni'],
                'receptor_cargo' => $prestamoData['receptor_cargo'],
                'fecha_inicio_prestamo' => $prestamoData['fecha_inicio_prestamo'],
                'fecha_estimada_devolucion' => $prestamoData['fecha_estimada_devolucion'],
                'fecha_devolucion_real' => $prestamoData['fecha_devolucion_real'],
                'observacion' => $data['observacion'] ?? null,
            ]);

            if (in_array($tipo, [Movimiento::TIPO_TRASLADO, Movimiento::TIPO_TRANSFERENCIA_INTERNA, Movimiento::TIPO_TRANSFERENCIA_EXTERNA, Movimiento::TIPO_DEVOLUCION], true)) {
                $equipo->oficina_id = $ubicacionDestino['oficina_id'];
            }

            $equipo->estado = $estadoNuevo;

            if ($equipo->offsetExists('_audit_before')) {
                $equipo->offsetUnset('_audit_before');
            }

            $equipo->save();
        });
    }

    private function validarReglas(Equipo $equipo, User $user, array $data, array $ubicacionOrigen): void
    {
        $tipo = $data['tipo_movimiento'];

        if (in_array($tipo, [Movimiento::TIPO_TRASLADO, Movimiento::TIPO_TRANSFERENCIA_INTERNA, Movimiento::TIPO_TRANSFERENCIA_EXTERNA], true)) {
            if ((int) $data['oficina_destino_id'] === (int) $ubicacionOrigen['oficina_id']) {
                throw ValidationException::withMessages(['oficina_destino_id' => 'La oficina de destino debe ser distinta de la oficina actual.']);
            }

            if (! $user->hasRole(User::ROLE_SUPERADMIN)
                && (int) $data['institucion_destino_id'] !== (int) $user->institution_id) {
                throw ValidationException::withMessages([
                    'institucion_destino_id' => 'No tiene permisos para transferir equipos entre instituciones.',
                ]);
            }
        }

        if ($tipo === Movimiento::TIPO_TRANSFERENCIA_INTERNA && (int) $data['institucion_destino_id'] !== (int) $ubicacionOrigen['institucion_id']) {
            throw ValidationException::withMessages([
                'institucion_destino_id' => 'La transferencia interna debe mantenerse en la misma institución.',
            ]);
        }

        if ($tipo === Movimiento::TIPO_PRESTAMO && $equipo->tienePrestamoActivo()) {
            throw ValidationException::withMessages(['tipo_movimiento' => 'El equipo ya tiene un préstamo activo.']);
        }

        if ($tipo === Movimiento::TIPO_DEVOLUCION) {
            $this->prestamoActivo($equipo);
        }
    }

    private function validarJerarquiaDestino(int $institucionDestinoId, int $servicioDestinoId, int $oficinaDestinoId): void
    {
        $service = Service::query()->find($servicioDestinoId);

        if ($service === null || (int) $service->institution_id !== $institucionDestinoId) {
            throw ValidationException::withMessages([
                'servicio_destino_id' => 'El servicio de destino no pertenece a la institución de destino seleccionada.',
            ]);
        }

        $office = Office::query()->find($oficinaDestinoId);

        if ($office === null || (int) $office->service_id !== $servicioDestinoId) {
            throw ValidationException::withMessages([
                'oficina_destino_id' => 'La oficina de destino no pertenece al servicio de destino seleccionado.',
            ]);
        }
    }

    private function prestamoActivo(Equipo $equipo): Movimiento
    {
        $prestamo = Movimiento::query()
            ->where('equipo_id', $equipo->id)
            ->where('tipo_movimiento', Movimiento::TIPO_PRESTAMO)
            ->whereNull('fecha_devolucion_real')
            ->latest('fecha')
            ->latest('id')
            ->first();

        if ($prestamo === null) {
            throw ValidationException::withMessages([
                'tipo_movimiento' => 'No existe préstamo activo para registrar la devolución.',
            ]);
        }

        return $prestamo;
    }
}
