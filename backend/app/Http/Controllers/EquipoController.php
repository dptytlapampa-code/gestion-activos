<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EquipoController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            'role:' . User::ROLE_SUPERADMIN . ',' . User::ROLE_ADMIN . ',' . User::ROLE_TECNICO,
        ])->only(['index', 'create', 'store', 'edit', 'update']);

        $this->middleware([
            'auth',
            'role:' . User::ROLE_SUPERADMIN . ',' . User::ROLE_ADMIN,
        ])->only('destroy');
    }

    public function index(Request $request): View
    {
        $equiposQuery = Equipo::query()
            ->with(['office.service.institution'])
            ->orderBy('tipo_equipo')
            ->orderBy('marca')
            ->orderBy('modelo');

        $equiposQuery->when($request->filled('institution_id'), function ($query) use ($request) {
            $query->whereHas('office.service.institution', function ($subQuery) use ($request) {
                $subQuery->where('institutions.id', $request->input('institution_id'));
            });
        });

        $equiposQuery->when($request->filled('service_id'), function ($query) use ($request) {
            $query->whereHas('office.service', function ($subQuery) use ($request) {
                $subQuery->where('services.id', $request->input('service_id'));
            });
        });

        $equiposQuery->when($request->filled('office_id'), function ($query) use ($request) {
            $query->where('office_id', $request->input('office_id'));
        });

        $equiposQuery->when($request->filled('tipo_equipo'), function ($query) use ($request) {
            $query->where('tipo_equipo', $request->input('tipo_equipo'));
        });

        $equiposQuery->when($request->filled('estado'), function ($query) use ($request) {
            $query->where('estado', $request->input('estado'));
        });

        $equipos = $equiposQuery->paginate(10)->withQueryString();

        $institutions = Institution::query()
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $services = Service::query()
            ->select('id', 'nombre', 'institution_id')
            ->orderBy('nombre')
            ->get();

        $offices = Office::query()
            ->select('id', 'nombre', 'service_id')
            ->orderBy('nombre')
            ->get();

        $tiposEquipos = Equipo::query()
            ->select('tipo_equipo')
            ->distinct()
            ->orderBy('tipo_equipo')
            ->pluck('tipo_equipo');

        return view('equipos.index', [
            'equipos' => $equipos,
            'institutions' => $institutions,
            'services' => $services,
            'offices' => $offices,
            'tiposEquipos' => $tiposEquipos,
            'estados' => Equipo::ESTADOS,
        ]);
    }

    public function create(): View
    {
        $institutions = Institution::query()
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $services = Service::query()
            ->select('id', 'nombre', 'institution_id')
            ->orderBy('nombre')
            ->get();

        $offices = Office::query()
            ->select('id', 'nombre', 'service_id')
            ->orderBy('nombre')
            ->get();

        return view('equipos.create', [
            'institutions' => $institutions,
            'services' => $services,
            'offices' => $offices,
            'estados' => Equipo::ESTADOS,
            'estadoDefault' => Equipo::ESTADO_OPERATIVO,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEquipo($request);

        Equipo::create($validated);

        return redirect()
            ->route('equipos.index')
            ->with('status', 'Equipo creado correctamente.');
    }

    public function edit(Equipo $equipo): View
    {
        $institutions = Institution::query()
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $services = Service::query()
            ->select('id', 'nombre', 'institution_id')
            ->orderBy('nombre')
            ->get();

        $offices = Office::query()
            ->select('id', 'nombre', 'service_id')
            ->orderBy('nombre')
            ->get();

        return view('equipos.edit', [
            'equipo' => $equipo->load('office.service.institution'),
            'institutions' => $institutions,
            'services' => $services,
            'offices' => $offices,
            'estados' => Equipo::ESTADOS,
        ]);
    }

    public function update(Request $request, Equipo $equipo): RedirectResponse
    {
        $validated = $this->validateEquipo($request, $equipo);

        $equipo->update($validated);

        return redirect()
            ->route('equipos.index')
            ->with('status', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo): RedirectResponse
    {
        $equipo->delete();

        return redirect()
            ->route('equipos.index')
            ->with('status', 'Equipo eliminado correctamente.');
    }

    private function validateEquipo(Request $request, ?Equipo $equipo = null): array
    {
        $equipoId = $equipo?->id;

        return $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where('institution_id', $request->input('institution_id')),
            ],
            'office_id' => [
                'required',
                Rule::exists('offices', 'id')->where('service_id', $request->input('service_id')),
            ],
            'tipo_equipo' => ['required', 'string', 'max:100'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'numero_serie' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipos', 'numero_serie')->ignore($equipoId),
            ],
            'bien_patrimonial' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipos', 'bien_patrimonial')->ignore($equipoId),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'estado' => ['required', Rule::in(Equipo::ESTADOS)],
            'fecha_ingreso' => ['required', 'date'],
        ]);
    }
}
