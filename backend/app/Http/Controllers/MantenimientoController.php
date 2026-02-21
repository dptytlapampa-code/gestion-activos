<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\EquipoStatus;
use App\Models\Mantenimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MantenimientoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Mantenimiento::class);

        $user = $request->user();

        abort_unless($user?->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN), 403);

        $mantenimientos = Mantenimiento::query()
            ->with(['equipo:id,tipo,numero_serie', 'estadoResultante:id,name,color'])
            ->when(! $user->hasRole(User::ROLE_SUPERADMIN), fn (Builder $query) => $query->where('institution_id', $user->institution_id))
            ->latest('fecha')
            ->latest('id')
            ->paginate(20);

        return view('mantenimientos.index', compact('mantenimientos'));
    }

    public function store(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('create', Mantenimiento::class);
        $this->authorize('view', $equipo);

        $user = $request->user();
        $institutionId = (int) $equipo->oficina?->service?->institution_id;

        if (! $user->hasRole(User::ROLE_SUPERADMIN) && $institutionId !== (int) $user->institution_id) {
            abort(403);
        }

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'tipo' => ['required', Rule::in(Mantenimiento::TIPOS)],
            'titulo' => ['required', 'string', 'max:150'],
            'detalle' => ['required', 'string'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'fecha_ingreso_st' => ['nullable', 'date'],
            'fecha_egreso_st' => ['nullable', 'date'],
            'override_rules' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($equipo, $validated, $user, $institutionId): void {
            $equipo->refresh()->load('equipoStatus');
            $this->applyRulesAndCreate($equipo, $validated, $user, $institutionId);
        });

        return back()->with('status', 'Mantenimiento registrado correctamente.');
    }

    public function edit(Mantenimiento $mantenimiento): View
    {
        $this->authorize('update', $mantenimiento);

        return view('mantenimientos.edit', [
            'mantenimiento' => $mantenimiento->load(['equipo', 'estadoResultante']),
            'tipos' => Mantenimiento::TIPOS,
        ]);
    }

    public function update(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('update', $mantenimiento);

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'tipo' => ['required', Rule::in(Mantenimiento::TIPOS)],
            'titulo' => ['required', 'string', 'max:150'],
            'detalle' => ['required', 'string'],
            'proveedor' => ['nullable', 'string', 'max:150'],
        ]);

        $mantenimiento->update($validated);

        return redirect()->route('equipos.show', $mantenimiento->equipo_id)->with('status', 'Mantenimiento actualizado.');
    }

    public function destroy(Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('delete', $mantenimiento);
        $equipoId = $mantenimiento->equipo_id;
        $mantenimiento->delete();

        return redirect()->route('equipos.show', $equipoId)->with('status', 'Mantenimiento eliminado.');
    }

    private function applyRulesAndCreate(Equipo $equipo, array $validated, User $user, int $institutionId): void
    {
        $operativa = EquipoStatus::query()->where('code', EquipoStatus::CODE_OPERATIVA)->firstOrFail();
        $enServicio = EquipoStatus::query()->where('code', EquipoStatus::CODE_EN_SERVICIO_TECNICO)->firstOrFail();
        $baja = EquipoStatus::query()->where('code', EquipoStatus::CODE_BAJA)->firstOrFail();

        $tipo = $validated['tipo'];
        $fecha = Carbon::parse($validated['fecha'])->toDateString();
        $override = (bool) ($validated['override_rules'] ?? false);

        $ultimo = $equipo->mantenimientos()->latest('fecha')->latest('id')->first();
        $externoAbierto = $equipo->mantenimientos()
            ->where('tipo', Mantenimiento::TIPO_EXTERNO)
            ->whereNull('fecha_egreso_st')
            ->latest('fecha')
            ->latest('id')
            ->first();

        if ($tipo === Mantenimiento::TIPO_EXTERNO && $ultimo?->tipo === Mantenimiento::TIPO_EXTERNO && ! $override) {
            abort(422, 'No se permiten dos mantenimientos externos consecutivos sin alta intermedia.');
        }

        if ($tipo === Mantenimiento::TIPO_ALTA && $externoAbierto === null && ! $override) {
            abort(422, 'No existe mantenimiento externo abierto para dar alta.');
        }

        $payload = [
            'equipo_id' => $equipo->id,
            'institution_id' => $institutionId,
            'created_by' => $user->id,
            'fecha' => $fecha,
            'tipo' => $tipo,
            'titulo' => $validated['titulo'],
            'detalle' => $validated['detalle'],
            'proveedor' => $validated['proveedor'] ?? null,
            'fecha_ingreso_st' => $validated['fecha_ingreso_st'] ?? null,
            'fecha_egreso_st' => $validated['fecha_egreso_st'] ?? null,
            'dias_en_servicio' => null,
            'estado_resultante_id' => null,
        ];

        if ($tipo === Mantenimiento::TIPO_EXTERNO) {
            $payload['fecha_ingreso_st'] = $payload['fecha_ingreso_st'] ?: $fecha;
            $payload['estado_resultante_id'] = $enServicio->id;
            $equipo->equipo_status_id = $enServicio->id;
            $equipo->estado = Equipo::ESTADO_MANTENIMIENTO;
            $equipo->save();
        }

        if ($tipo === Mantenimiento::TIPO_ALTA) {
            $payload['fecha_egreso_st'] = $payload['fecha_egreso_st'] ?: $fecha;
            if ($externoAbierto !== null) {
                $ingreso = Carbon::parse($externoAbierto->fecha_ingreso_st ?: $externoAbierto->fecha);
                $egreso = Carbon::parse($payload['fecha_egreso_st']);
                $dias = max($ingreso->diffInDays($egreso), 0);
                $payload['dias_en_servicio'] = $dias;

                $externoAbierto->update([
                    'fecha_egreso_st' => $payload['fecha_egreso_st'],
                    'dias_en_servicio' => $dias,
                ]);

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'action' => 'maintenance_external_closed',
                    'auditable_type' => Mantenimiento::class,
                    'auditable_id' => $externoAbierto->id,
                    'before' => ['fecha_egreso_st' => null, 'dias_en_servicio' => null],
                    'after' => ['fecha_egreso_st' => $payload['fecha_egreso_st'], 'dias_en_servicio' => $dias],
                    'ip' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);
            } elseif ($user->hasRole(User::ROLE_SUPERADMIN)) {
                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'action' => 'maintenance_warning',
                    'auditable_type' => Equipo::class,
                    'auditable_id' => $equipo->id,
                    'before' => null,
                    'after' => ['warning' => 'Alta sin externo abierto'],
                    'ip' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);
            }

            $payload['estado_resultante_id'] = $operativa->id;
            $equipo->equipo_status_id = $operativa->id;
            $equipo->estado = Equipo::ESTADO_OPERATIVO;
            $equipo->save();
        }

        if ($tipo === Mantenimiento::TIPO_BAJA) {
            $payload['estado_resultante_id'] = $baja->id;
            $equipo->equipo_status_id = $baja->id;
            $equipo->estado = Equipo::ESTADO_BAJA;
            $equipo->save();
        }

        if (in_array($tipo, [Mantenimiento::TIPO_INTERNO, Mantenimiento::TIPO_OTRO], true)) {
            $payload['estado_resultante_id'] = $equipo->equipo_status_id;
        }

        Mantenimiento::query()->create($payload);
    }
}
