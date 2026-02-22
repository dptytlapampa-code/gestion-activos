<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MovimientoController extends Controller
{
    private const TIPOS_MOVIMIENTO = [
        'mantenimiento',
        'prestamo',
        'baja',
        'traslado',
        'transferencia_interna',
        'transferencia_externa',
        'devolucion',
    ];

    public function create(Request $request): View
    {
        $equipoId = $request->integer('equipo_id');
        abort_if($equipoId <= 0, 404);

        $equipo = Equipo::query()->with('oficina.service.institution')->findOrFail($equipoId);
        $this->authorize('update', $equipo);

        $user = $request->user();
        $currentInstitutionId = (int) $equipo->oficina?->service?->institution_id;

        $instituciones = $this->scopedInstituciones($user);
        $servicios = $this->scopedServicios($user);
        $oficinas = $this->scopedOficinas($user);

        return view('movimientos.create', [
            'equipo' => $equipo,
            'tipos_movimiento' => self::TIPOS_MOVIMIENTO,
            'instituciones' => $instituciones,
            'servicios' => $servicios,
            'oficinas' => $oficinas,
            'current_institution_id' => $currentInstitutionId,
        ]);
    }

    public function store(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('update', $equipo);

        $user = $request->user();
        $currentOfficeId = (int) $equipo->oficina_id;
        $currentInstitutionId = (int) $equipo->oficina?->service?->institution_id;

        $validated = $request->validate([
            'tipo_movimiento' => ['required', 'string', 'in:'.implode(',', self::TIPOS_MOVIMIENTO)],
            'institucion_destino_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')],
            'servicio_destino_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'oficina_destino_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
            'receptor_nombre' => ['nullable', 'string', 'max:255'],
            'receptor_dni' => ['nullable', 'string', 'max:50'],
            'receptor_cargo' => ['nullable', 'string', 'max:255'],
            'fecha_inicio_prestamo' => ['nullable', 'date'],
            'fecha_estimada_devolucion' => ['nullable', 'date', 'after_or_equal:fecha_inicio_prestamo'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $tipo = $validated['tipo_movimiento'];

        if (in_array($tipo, ['traslado', 'transferencia_externa'], true) && ! $user->hasRole(User::ROLE_SUPERADMIN)) {
            $validated['institucion_destino_id'] = $user->institution_id;
        }

        if ($equipo->estado === Equipo::ESTADO_BAJA) {
            throw ValidationException::withMessages([
                'equipo' => 'El equipo se encuentra en baja y no admite nuevos movimientos.',
            ]);
        }

        $this->validateByTipo($equipo, $validated, $tipo, $currentInstitutionId, $currentOfficeId);

        DB::transaction(function () use ($equipo, $validated, $tipo): void {
            $equipo->refresh();

            $ubicacionOrigen = $this->resolveCurrentLocation($equipo);
            $ubicacionDestino = [
                'institucion_id' => null,
                'servicio_id' => null,
                'oficina_id' => null,
            ];

            $estadoNuevo = $equipo->estado;
            $prestamoData = [
                'receptor_nombre' => null,
                'receptor_dni' => null,
                'receptor_cargo' => null,
                'fecha_inicio_prestamo' => null,
                'fecha_estimada_devolucion' => null,
            ];

            if (in_array($tipo, ['traslado', 'transferencia_interna', 'transferencia_externa'], true)) {
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

            if ($tipo === 'mantenimiento') {
                $estadoNuevo = Equipo::ESTADO_MANTENIMIENTO;
                $equipo->equipo_status_id = (int) EquipoStatus::query()->where('code', EquipoStatus::CODE_EN_SERVICIO_TECNICO)->value('id');
            }

            if ($tipo === 'prestamo') {
                $estadoNuevo = 'prestamo';
                $prestamoData = [
                    'receptor_nombre' => $validated['receptor_nombre'],
                    'receptor_dni' => $validated['receptor_dni'] ?? null,
                    'receptor_cargo' => $validated['receptor_cargo'] ?? null,
                    'fecha_inicio_prestamo' => $validated['fecha_inicio_prestamo'],
                    'fecha_estimada_devolucion' => $validated['fecha_estimada_devolucion'] ?? null,
                ];
            }

            if ($tipo === 'devolucion') {
                $prestamo = $this->resolvePrestamoActivo($equipo);
                if ($prestamo === null) {
                    throw ValidationException::withMessages(['tipo_movimiento' => 'No existe préstamo activo para registrar la devolución.']);
                }

                $ubicacionDestino = [
                    'institucion_id' => $prestamo->institucion_origen_id,
                    'servicio_id' => $prestamo->servicio_origen_id,
                    'oficina_id' => $prestamo->oficina_origen_id,
                ];

                $estadoNuevo = Equipo::ESTADO_OPERATIVO;
            }

            if ($tipo === 'baja') {
                $estadoNuevo = Equipo::ESTADO_BAJA;
                $equipo->equipo_status_id = (int) EquipoStatus::query()->where('code', EquipoStatus::CODE_BAJA)->value('id');
            }

            Movimiento::query()->create([
                'equipo_id' => $equipo->id,
                'user_id' => auth()->id(),
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
                'observacion' => $validated['observacion'] ?? null,
            ]);

            $equipo->estado = $estadoNuevo;

            if (in_array($tipo, ['traslado', 'transferencia_interna', 'transferencia_externa', 'devolucion'], true)) {
                $equipo->oficina_id = (int) $ubicacionDestino['oficina_id'];
            }

            if ($equipo->offsetExists('_audit_before')) {
                $equipo->offsetUnset('_audit_before');
            }

            $equipo->save();
        });

        return redirect()->route('equipos.show', $equipo)->with('status', 'Movimiento registrado correctamente.');
    }

    private function validateByTipo(Equipo $equipo, array $validated, string $tipo, int $currentInstitutionId, int $currentOfficeId): void
    {
        if (in_array($tipo, ['traslado', 'transferencia_interna', 'transferencia_externa'], true)) {
            foreach (['institucion_destino_id', 'servicio_destino_id', 'oficina_destino_id'] as $field) {
                if (empty($validated[$field])) {
                    throw ValidationException::withMessages([$field => 'Este campo es obligatorio para el tipo seleccionado.']);
                }
            }
        }

        if ($tipo === 'transferencia_interna' && (int) $validated['institucion_destino_id'] !== $currentInstitutionId) {
            throw ValidationException::withMessages([
                'institucion_destino_id' => 'La transferencia interna debe mantenerse en la misma institución.',
            ]);
        }

        if (in_array($tipo, ['traslado', 'transferencia_interna', 'transferencia_externa'], true)
            && (int) $validated['oficina_destino_id'] === $currentOfficeId) {
            throw ValidationException::withMessages([
                'oficina_destino_id' => 'La oficina de destino debe ser distinta de la oficina actual.',
            ]);
        }

        if ($tipo === 'prestamo') {
            foreach (['receptor_nombre', 'receptor_dni', 'receptor_cargo', 'fecha_inicio_prestamo', 'fecha_estimada_devolucion'] as $field) {
                if (empty($validated[$field])) {
                    throw ValidationException::withMessages([$field => 'Este campo es obligatorio para préstamo.']);
                }
            }

            if ($this->resolvePrestamoActivo($equipo) !== null) {
                throw ValidationException::withMessages(['tipo_movimiento' => 'El equipo ya tiene un préstamo activo.']);
            }
        }
    }

    private function resolvePrestamoActivo(Equipo $equipo): ?Movimiento
    {
        $ultimoPrestamo = Movimiento::query()
            ->where('equipo_id', $equipo->id)
            ->where('tipo_movimiento', 'prestamo')
            ->latest('fecha')
            ->latest('id')
            ->first();

        if ($ultimoPrestamo === null) {
            return null;
        }

        $devolucionPosterior = Movimiento::query()
            ->where('equipo_id', $equipo->id)
            ->where('tipo_movimiento', 'devolucion')
            ->where('fecha', '>=', $ultimoPrestamo->fecha)
            ->exists();

        return $devolucionPosterior ? null : $ultimoPrestamo;
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

    private function scopedInstituciones(?User $user)
    {
        return Institution::query()
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), fn ($query) => $query->where('id', $user->institution_id))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    private function scopedServicios(?User $user)
    {
        return Service::query()
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), fn ($query) => $query->where('institution_id', $user->institution_id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'institution_id']);
    }

    private function scopedOficinas(?User $user)
    {
        return Office::query()
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), fn ($query) => $query->whereHas('service', fn ($q) => $q->where('institution_id', $user->institution_id)))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'service_id']);
    }
}
