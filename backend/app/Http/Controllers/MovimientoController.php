<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MovimientoController extends Controller
{
    private const TIPOS_MOVIMIENTO = [
        'mantenimiento',
        'prestamo',
        'baja',
        'traslado',
    ];

    public function store(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('update', $equipo);

        $user = $request->user();

        $validated = $request->validate([
            'tipo_movimiento' => ['required', 'string', 'in:'.implode(',', self::TIPOS_MOVIMIENTO)],
            'institucion_destino_id' => [
                Rule::requiredIf($request->input('tipo_movimiento') === 'traslado'),
                'nullable',
                'integer',
                Rule::exists('institutions', 'id')->when(
                    $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                    fn ($query) => $query->where('id', $user->institution_id)
                ),
            ],
            'servicio_destino_id' => [
                Rule::requiredIf($request->input('tipo_movimiento') === 'traslado'),
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(function ($query) use ($request): void {
                    $institutionId = $request->integer('institucion_destino_id');

                    if ($institutionId > 0) {
                        $query->where('institution_id', $institutionId);
                    }
                }),
            ],
            'oficina_destino_id' => [
                Rule::requiredIf($request->input('tipo_movimiento') === 'traslado'),
                'nullable',
                'integer',
                Rule::exists('offices', 'id')->where(function ($query) use ($request): void {
                    $serviceId = $request->integer('servicio_destino_id');

                    if ($serviceId > 0) {
                        $query->where('service_id', $serviceId);
                    }
                }),
            ],
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
                $this->validateDestinationHierarchy(
                    (int) $validated['institucion_destino_id'],
                    (int) $validated['servicio_destino_id'],
                    (int) $validated['oficina_destino_id'],
                );

                $ubicacionDestino = [
                    'institucion_id' => (int) $validated['institucion_destino_id'],
                    'servicio_id' => (int) $validated['servicio_destino_id'],
                    'oficina_id' => (int) $validated['oficina_destino_id'],
                ];

                $estadoNuevo = Equipo::ESTADO_OPERATIVO;
            }

            if ($validated['tipo_movimiento'] === 'mantenimiento') {
                $estadoNuevo = Equipo::ESTADO_MANTENIMIENTO;
                $equipo->equipo_status_id = (int) EquipoStatus::query()->where('code', EquipoStatus::CODE_EN_SERVICIO_TECNICO)->value('id');
            }

            if ($validated['tipo_movimiento'] === 'prestamo') {
                $estadoNuevo = 'prestamo';
            }

            if ($validated['tipo_movimiento'] === 'baja') {
                $estadoNuevo = Equipo::ESTADO_BAJA;
                $equipo->equipo_status_id = (int) EquipoStatus::query()->where('code', EquipoStatus::CODE_BAJA)->value('id');
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

            $equipo->estado = $estadoNuevo;

            if ($validated['tipo_movimiento'] === 'traslado') {
                $equipo->oficina_id = (int) $validated['oficina_destino_id'];
            }

            $equipo->save();
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

    private function validateDestinationHierarchy(int $institucionDestinoId, int $servicioDestinoId, int $oficinaDestinoId): void
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
