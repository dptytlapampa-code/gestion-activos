<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMantenimientoRequest;
use App\Http\Requests\UpdateMantenimientoRequest;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Services\MantenimientoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MantenimientoController extends Controller
{
    public function __construct(private readonly MantenimientoService $mantenimientoService) {}

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

    public function store(StoreMantenimientoRequest $request, Equipo $equipo): RedirectResponse
    {
        try {
            $this->mantenimientoService->registrar($equipo, $request->user(), $request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('equipos.show', $equipo)
                ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                ->with('error', 'No fue posible registrar el mantenimiento. Intente nuevamente en unos minutos.');
        }

        return redirect()
            ->route('equipos.show', $equipo)
            ->with('status', 'Mantenimiento registrado correctamente.');
    }

    public function edit(Mantenimiento $mantenimiento): View|RedirectResponse
    {
        $this->authorize('update', $mantenimiento);

        if (! $mantenimiento->canBeManuallyChanged()) {
            return redirect()
                ->route('equipos.show', $mantenimiento->equipo_id)
                ->with('error', $this->mantenimientoService->mensajeRegistroBloqueado());
        }

        return view('mantenimientos.edit', [
            'mantenimiento' => $mantenimiento->load(['equipo', 'estadoResultante']),
        ]);
    }

    public function update(UpdateMantenimientoRequest $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('update', $mantenimiento);

        try {
            $this->mantenimientoService->actualizar($mantenimiento, $request->validated());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('equipos.show', $mantenimiento->equipo_id)
                ->with('error', $this->firstValidationError($exception));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('equipos.show', $mantenimiento->equipo_id)
                ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                ->with('error', 'No fue posible actualizar la nota tecnica. Intente nuevamente.');
        }

        return redirect()->route('equipos.show', $mantenimiento->equipo_id)->with('status', 'Mantenimiento actualizado.');
    }

    public function destroy(Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('delete', $mantenimiento);
        try {
            $equipoId = $mantenimiento->equipo_id;
            $this->mantenimientoService->eliminar($mantenimiento);

            return redirect()->route('equipos.show', $equipoId)->with('status', 'Mantenimiento eliminado.');
        } catch (ValidationException $exception) {
            return redirect()
                ->route('equipos.show', $mantenimiento->equipo_id)
                ->with('error', $this->firstValidationError($exception));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('equipos.show', $mantenimiento->equipo_id)
                ->with('error', 'No fue posible eliminar la nota tecnica. Intente nuevamente.');
        }
    }

    private function firstValidationError(ValidationException $exception): string
    {
        return (string) collect($exception->errors())->flatten()->first();
    }
}
