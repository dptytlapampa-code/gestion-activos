<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovimientoController extends Controller
{
    private const TIPOS_MOVIMIENTO = [
        'traslado',
        'mantenimiento',
        'prestamo',
        'baja',
    ];

    public function store(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('update', $equipo);

        $validated = $request->validate([
            'tipo_movimiento' => ['required', 'string', 'in:'.implode(',', self::TIPOS_MOVIMIENTO)],
            'oficina_destino_id' => ['nullable', 'integer', 'exists:offices,id'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($equipo->estado === Equipo::ESTADO_BAJA) {
            throw ValidationException::withMessages([
                'equipo' => 'El equipo se encuentra en baja y no admite nuevos movimientos.',
            ]);
        }

        DB::transaction(function () use ($equipo, $validated): void {
            $equipo->refresh();

            if ($equipo->estado === Equipo::ESTADO_BAJA) {
                throw ValidationException::withMessages([
                    'equipo' => 'El equipo se encuentra en baja y no admite nuevos movimientos.',
                ]);
            }

            $ubicacionOrigen = $this->resolveCurrentLocation($equipo);
            $ubicacionDestino = [
                'institucion_id' => null,
                'servicio_id' => null,
                'oficina_id' => null,
            ];

            $estadoNuevo = $equipo->estado;

            if ($validated['tipo_movimiento'] === 'traslado') {
                $ubicacionDestino = $this->resolveDestinationLocation($validated['oficina_destino_id'] ?? null);
                $estadoNuevo = Equipo::ESTADO_OPERATIVO;
            }

            if ($validated['tipo_movimiento'] === 'mantenimiento') {
                $estadoNuevo = Equipo::ESTADO_MANTENIMIENTO;
            }

            if ($validated['tipo_movimiento'] === 'prestamo') {
                $estadoNuevo = 'prestamo';
            }

            if ($validated['tipo_movimiento'] === 'baja') {
                $estadoNuevo = Equipo::ESTADO_BAJA;
            }

            Movimiento::query()->create([
                'equipo_id' => $equipo->id,
                'user_id' => auth()->id(),
                'tipo_movimiento' => $validated['tipo_movimiento'],
                'fecha' => now(),
                'institucion_origen_id' => $ubicacionOrigen['institucion_id'],
                'servicio_origen_id' => $ubicacionOrigen['servicio_id'],
                'oficina_origen_id' => $ubicacionOrigen['oficina_id'],
                'institucion_destino_id' => $ubicacionDestino['institucion_id'],
                'servicio_destino_id' => $ubicacionDestino['servicio_id'],
                'oficina_destino_id' => $ubicacionDestino['oficina_id'],
                'observacion' => $validated['observacion'] ?? null,
            ]);

            $equipo->update([
                'oficina_id' => $validated['tipo_movimiento'] === 'traslado'
                    ? $ubicacionDestino['oficina_id']
                    : $equipo->oficina_id,
                'estado' => $estadoNuevo,
            ]);
        });

        return redirect()->route('equipos.show', $equipo)->with('status', 'Movimiento registrado correctamente.');
    }

    /**
     * @return array{institucion_id:int|null,servicio_id:int|null,oficina_id:int|null}
     */
    private function resolveCurrentLocation(Equipo $equipo): array
    {
        $office = Office::query()
            ->with('service.institution')
            ->find($equipo->oficina_id);

        return $this->mapOfficeLocation($office);
    }

    /**
     * @return array{institucion_id:int,servicio_id:int,oficina_id:int}
     */
    private function resolveDestinationLocation(?int $oficinaDestinoId): array
    {
        if ($oficinaDestinoId === null) {
            throw ValidationException::withMessages([
                'oficina_destino_id' => 'Debe seleccionar una oficina de destino para un traslado.',
            ]);
        }

        $office = Office::query()
            ->with('service.institution')
            ->find($oficinaDestinoId);

        if ($office === null || $office->service === null || $office->service->institution === null) {
            throw ValidationException::withMessages([
                'oficina_destino_id' => 'La oficina de destino no posee una jerarquía válida de servicio e institución.',
            ]);
        }

        return $this->mapOfficeLocation($office);
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
}
