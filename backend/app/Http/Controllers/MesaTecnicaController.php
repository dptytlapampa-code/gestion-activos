<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMesaTecnicaEntregaRequest;
use App\Http\Requests\StoreMesaTecnicaRecepcionRequest;
use App\Models\Equipo;
use App\Models\User;
use App\Services\MesaTecnicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MesaTecnicaController extends Controller
{
    public function __construct(private readonly MesaTecnicaService $mesaTecnicaService) {}

    public function index(Request $request): View
    {
        $this->authorize('mesa_tecnica_access');

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return view('mesa-tecnica.index', array_merge(
            $this->mesaTecnicaService->dashboard($user),
            [
                'initialModal' => old('mesa_modal'),
                'restoredSelectedEquipo' => $this->mesaTecnicaService->selectedEquipo($user, old('equipo_id')),
                'mesaResult' => session('mesa_tecnica_result'),
            ],
        ));
    }

    public function storeRecepcion(StoreMesaTecnicaRecepcionRequest $request): RedirectResponse
    {
        try {
            $acta = $this->mesaTecnicaService->registrarRecepcion($request->user(), $request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->friendlyErrorRedirect($exception);
        }

        return redirect()
            ->route('mesa-tecnica.index')
            ->with('status', 'Recepcion registrada correctamente. Se emitio el acta correspondiente.')
            ->with('mesa_tecnica_result', $this->mesaTecnicaService->operationResult($acta));
    }

    public function storeEntrega(StoreMesaTecnicaEntregaRequest $request): RedirectResponse
    {
        try {
            $acta = $this->mesaTecnicaService->registrarEntrega($request->user(), $request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->friendlyErrorRedirect($exception);
        }

        return redirect()
            ->route('mesa-tecnica.index')
            ->with('status', 'Entrega registrada correctamente. Se emitio el acta correspondiente.')
            ->with('mesa_tecnica_result', $this->mesaTecnicaService->operationResult($acta));
    }

    public function label(Equipo $equipo): View
    {
        $this->authorize('mesa_tecnica_access');
        $this->authorize('view', $equipo);

        return view('mesa-tecnica.label', $this->mesaTecnicaService->labelData($equipo));
    }
}
