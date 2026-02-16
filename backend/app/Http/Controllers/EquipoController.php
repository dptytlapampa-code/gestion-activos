<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use App\Models\Equipo;
use App\Models\Institution;
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
            $equiposQuery->whereHas('oficina.service', function ($query) use ($user) {
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

        $data = $request->safe()->only([
            'tipo_equipo_id',
            'marca',
            'modelo',
            'bien_patrimonial',
            'estado',
            'fecha_ingreso',
            'oficina_id',
        ]);
        $data['tipo'] = $tipoEquipo->nombre;
        $data['numero_serie'] = $request->input('numero_serie');

        Equipo::query()->create($data);

        return redirect()->route('equipos.index')->with('status', 'Equipo creado correctamente.');
    }

    public function show(Equipo $equipo): View
    {
        return view('equipos.show', [
            'equipo' => $equipo->load(['oficina.service.institution', 'tipoEquipo']),
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

        $data = $request->safe()->only([
            'tipo_equipo_id',
            'marca',
            'modelo',
            'bien_patrimonial',
            'estado',
            'fecha_ingreso',
            'oficina_id',
        ]);
        $data['tipo'] = $tipoEquipo->nombre;
        $data['numero_serie'] = $request->input('numero_serie');

        $equipo->update($data);

        return redirect()->route('equipos.index')->with('status', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo): RedirectResponse
    {
        $equipo->delete();

        return redirect()->route('equipos.index')->with('status', 'Equipo eliminado correctamente.');
    }
}
