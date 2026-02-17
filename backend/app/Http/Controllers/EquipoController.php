<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Movimiento;
use App\Models\Office;
use App\Models\Service;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipoController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(Equipo::class, 'equipo');
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipo::class);

        $user = $request->user();

        $equiposQuery = Equipo::query()->with(['oficina.service.institution', 'tipoEquipo']);

        if ($user->hasRole(User::ROLE_ADMIN)) {
            $equiposQuery->whereHas('oficina.service', function ($query) use ($user): void {
                $query->where('institution_id', $user->institution_id);
            });
        }

        $equiposQuery
            ->when($request->filled('tipo'), fn ($query) => $query->where('tipo', 'ilike', '%'.$request->string('tipo').'%'))
            ->when($request->filled('marca'), fn ($query) => $query->where('marca', 'ilike', '%'.$request->string('marca').'%'))
            ->when($request->filled('modelo'), fn ($query) => $query->where('modelo', 'ilike', '%'.$request->string('modelo').'%'))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->string('estado')))
            ->orderBy('tipo')
            ->orderBy('marca')
            ->orderBy('modelo');

        return view('equipos.index', [
            'equipos' => $equiposQuery->paginate(15)->withQueryString(),
            'estados' => Equipo::ESTADOS,
        ]);
    }

    public function create(): View
    {
        $instituciones = Institution::query()->orderBy('nombre')->get(['id', 'nombre']);
        $servicios = Service::query()->orderBy('nombre')->get(['id', 'nombre', 'institution_id']);
        $oficinas = Office::query()->orderBy('nombre')->get(['id', 'nombre', 'service_id']);

        return view('equipos.create', [
            'estados' => Equipo::ESTADOS,
            'instituciones' => $instituciones,
            'servicios' => $servicios,
            'oficinas' => $oficinas,
        ]);
    }

    public function store(StoreEquipoRequest $request): RedirectResponse
    {
        $tipoEquipo = TipoEquipo::query()->findOrFail($request->integer('tipo_equipo_id'));

        $validated = $request->validated();

        $data = [
            'tipo_equipo_id' => $validated['tipo_equipo_id'],
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'bien_patrimonial' => $validated['bien_patrimonial'],
            'estado' => $validated['estado'],
            'fecha_ingreso' => $validated['fecha_ingreso'],
            'oficina_id' => $validated['office_id'],
            'tipo' => $tipoEquipo->nombre,
            'numero_serie' => $validated['numero_serie'],
        ];

        $equipo = Equipo::query()->create($data);

        $oficinaDestino = Office::query()
            ->with('service.institution')
            ->find($equipo->oficina_id);

        $ubicacionDestino = $this->mapOfficeLocation($oficinaDestino);

        Movimiento::query()->create([
            'equipo_id' => $equipo->id,
            'user_id' => auth()->id(),
            'tipo_movimiento' => 'ingreso',
            'fecha' => now(),
            'institucion_destino_id' => $ubicacionDestino['institucion_id'],
            'servicio_destino_id' => $ubicacionDestino['servicio_id'],
            'oficina_destino_id' => $ubicacionDestino['oficina_id'],
            'observacion' => 'Ingreso de equipo',
        ]);

        return redirect()->route('equipos.index')->with('status', 'Equipo creado correctamente.');
    }

    public function show(Equipo $equipo): View
    {
        $equipo->load([
            'oficina.service.institution',
            'tipoEquipo',
            'movimientos.user',
        ]);

        $officeIds = $equipo->movimientos
            ->flatMap(fn (Movimiento $movimiento) => [
                $movimiento->oficina_origen_id,
                $movimiento->oficina_destino_id,
            ])
            ->filter()
            ->unique()
            ->values();

        $offices = Office::query()
            ->with('service.institution')
            ->whereIn('id', $officeIds)
            ->get()
            ->keyBy('id');

        $instituciones = Institution::query()->orderBy('nombre')->get(['id', 'nombre']);
        $servicios = Service::query()->orderBy('nombre')->get(['id', 'nombre', 'institution_id']);
        $oficinas = Office::query()->orderBy('nombre')->get(['id', 'nombre', 'service_id']);

        return view('equipos.show', [
            'equipo' => $equipo,
            'offices' => $offices,
            'instituciones' => $instituciones,
            'servicios' => $servicios,
            'oficinas' => $oficinas,
        ]);
    }

    public function edit(Equipo $equipo): View
    {
        $instituciones = Institution::query()->orderBy('nombre')->get(['id', 'nombre']);
        $servicios = Service::query()->orderBy('nombre')->get(['id', 'nombre', 'institution_id']);
        $oficinas = Office::query()->orderBy('nombre')->get(['id', 'nombre', 'service_id']);

        return view('equipos.edit', [
            'equipo' => $equipo->load(['oficina.service.institution', 'tipoEquipo']),
            'estados' => Equipo::ESTADOS,
            'instituciones' => $instituciones,
            'servicios' => $servicios,
            'oficinas' => $oficinas,
        ]);
    }

    public function update(UpdateEquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        $tipoEquipo = TipoEquipo::query()->findOrFail($request->integer('tipo_equipo_id'));
        $oficinaOriginal = Office::query()
            ->with('service.institution')
            ->find($equipo->oficina_id);

        $validated = $request->validated();

        $data = [
            'tipo_equipo_id' => $validated['tipo_equipo_id'],
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'bien_patrimonial' => $validated['bien_patrimonial'],
            'estado' => $validated['estado'],
            'fecha_ingreso' => $validated['fecha_ingreso'],
            'oficina_id' => $validated['office_id'],
            'tipo' => $tipoEquipo->nombre,
            'numero_serie' => $validated['numero_serie'],
        ];

        $equipo->update($data);

        if ($equipo->wasChanged('oficina_id')) {
            $ubicacionOrigen = $this->mapOfficeLocation($oficinaOriginal);
            $oficinaDestino = Office::query()
                ->with('service.institution')
                ->find($equipo->oficina_id);
            $ubicacionDestino = $this->mapOfficeLocation($oficinaDestino);

            Movimiento::query()->create([
                'equipo_id' => $equipo->id,
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'traslado',
                'fecha' => now(),
                'institucion_origen_id' => $ubicacionOrigen['institucion_id'],
                'servicio_origen_id' => $ubicacionOrigen['servicio_id'],
                'oficina_origen_id' => $ubicacionOrigen['oficina_id'],
                'institucion_destino_id' => $ubicacionDestino['institucion_id'],
                'servicio_destino_id' => $ubicacionDestino['servicio_id'],
                'oficina_destino_id' => $ubicacionDestino['oficina_id'],
                'observacion' => 'Traslado de ubicación',
            ]);
        }

        return redirect()->route('equipos.index')->with('status', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo): RedirectResponse
    {
        $equipo->delete();

        return redirect()->route('equipos.index')->with('status', 'Equipo eliminado correctamente.');
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
