<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoEquipoRequest;
use App\Http\Requests\UpdateTipoEquipoRequest;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipoEquipoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeRead($request);

        $tipos_equipos = TipoEquipo::query()
            ->when(
                $request->filled('q'),
                fn ($query) => $query->where('nombre', 'ilike', '%'.$request->string('q').'%')
            )
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('tipos_equipos.index', [
            'tipos_equipos' => $tipos_equipos,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeWrite($request);

        return view('tipos_equipos.create');
    }

    public function store(StoreTipoEquipoRequest $request): RedirectResponse
    {
        TipoEquipo::query()->create($request->validated());

        return redirect()
            ->route('tipos-equipos.index')
            ->with('status', 'Tipo de equipo creado correctamente.');
    }

    public function show(Request $request, TipoEquipo $tipo_equipo): View
    {
        $this->authorizeRead($request);

        return view('tipos_equipos.show', [
            'tipo_equipo' => $tipo_equipo->loadCount('equipos'),
        ]);
    }

    public function edit(Request $request, TipoEquipo $tipo_equipo): View
    {
        $this->authorizeWrite($request);

        return view('tipos_equipos.edit', [
            'tipo_equipo' => $tipo_equipo,
        ]);
    }

    public function update(UpdateTipoEquipoRequest $request, TipoEquipo $tipo_equipo): RedirectResponse
    {
        $tipo_equipo->update($request->validated());

        return redirect()
            ->route('tipos-equipos.index')
            ->with('status', 'Tipo de equipo actualizado correctamente.');
    }

    public function destroy(Request $request, TipoEquipo $tipo_equipo): RedirectResponse
    {
        $this->authorizeWrite($request);

        $tipo_equipo->delete();

        return redirect()
            ->route('tipos-equipos.index')
            ->with('status', 'Tipo de equipo eliminado correctamente.');
    }

    private function authorizeRead(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER),
            403
        );
    }

    private function authorizeWrite(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN),
            403
        );
    }
}
