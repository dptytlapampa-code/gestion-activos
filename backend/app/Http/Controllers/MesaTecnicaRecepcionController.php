<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseRecepcionTecnicaRequest;
use App\Http\Requests\StoreRecepcionTecnicaIncorporacionRequest;
use App\Http\Requests\StoreRecepcionTecnicaRequest;
use App\Http\Requests\UpdateRecepcionTecnicaStatusRequest;
use App\Models\Equipo;
use App\Models\RecepcionTecnica;
use App\Services\MesaTecnicaService;
use App\Services\RecepcionTecnicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MesaTecnicaRecepcionController extends Controller
{
    public function __construct(private readonly RecepcionTecnicaService $recepcionTecnicaService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', RecepcionTecnica::class);

        $listing = $this->recepcionTecnicaService->listingState($request);
        $filters = $this->recepcionTecnicaService->filtersFromRequest($request);
        $tray = $this->recepcionTecnicaService->operationalTrayFromRequest($request);

        $recepcionesTecnicas = $this->recepcionTecnicaService
            ->buildOperationalTrayQuery($request->user(), $listing->search, $filters, $tray)
            ->paginate($listing->perPage)
            ->withQueryString();

        return view('mesa-tecnica.recepciones-tecnicas.index', [
            'recepcionesTecnicas' => $recepcionesTecnicas,
            'listing' => $listing,
            'filters' => $filters,
            'tray' => $tray,
            'trayCounts' => $this->recepcionTecnicaService->operationalTrayCounts($request->user(), $listing->search, $filters),
            'trayLabels' => RecepcionTecnicaService::TRAY_LABELS,
            'statusOptions' => $this->recepcionTecnicaService->statusOptions(),
            'hasActiveFilters' => $this->recepcionTecnicaService->hasActiveFilters($listing->search, $filters),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', RecepcionTecnica::class);

        return view('mesa-tecnica.recepciones-tecnicas.create', [
            'defaultReceptionTimestamp' => $this->recepcionTecnicaService->defaultReceptionTimestamp(),
            'statusOptions' => $this->recepcionTecnicaService->statusOptions(),
            'restoredSelectedEquipo' => app(MesaTecnicaService::class)->selectedEquipo(request()->user(), old('equipo_id')),
            'equipmentStates' => array_values(array_filter(
                Equipo::ESTADOS,
                fn (string $estado): bool => $estado !== Equipo::ESTADO_EN_MANTENIMIENTO
            )),
        ]);
    }

    public function store(StoreRecepcionTecnicaRequest $request): RedirectResponse
    {
        try {
            $recepcionTecnica = $this->recepcionTecnicaService->create($request->user(), $request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->friendlyErrorRedirect($exception);
        }

        $status = $recepcionTecnica->equipo_creado_id !== null
            ? 'Ingreso tecnico registrado correctamente. El equipo fue incorporado al sistema y quedo vinculado al ticket de ingreso.'
            : 'Ingreso tecnico registrado correctamente.';

        return redirect()
            ->route('mesa-tecnica.recepciones-tecnicas.show', $recepcionTecnica)
            ->with('status', $status);
    }

    public function show(Request $request, RecepcionTecnica $recepcionTecnica): View
    {
        $this->authorize('view', $recepcionTecnica);

        return view(
            'mesa-tecnica.recepciones-tecnicas.show',
            array_merge(
                $this->recepcionTecnicaService->detailData($recepcionTecnica, $request->query('return_to')),
                [
                    'equipmentStates' => array_values(array_filter(
                        Equipo::ESTADOS,
                        fn (string $estado): bool => $estado !== Equipo::ESTADO_EN_MANTENIMIENTO
                    )),
                ],
            )
        );
    }

    public function print(Request $request, RecepcionTecnica $recepcionTecnica): View|RedirectResponse
    {
        $this->authorize('print', $recepcionTecnica);

        try {
            return view(
                'mesa-tecnica.recepciones-tecnicas.print',
                $this->recepcionTecnicaService->registerPrint($request->user(), $recepcionTecnica)
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->friendlyErrorRedirect($exception, false);
        }
    }

    public function createIncorporation(RecepcionTecnica $recepcionTecnica): View
    {
        $this->authorize('incorporate', $recepcionTecnica);

        return view(
            'mesa-tecnica.recepciones-tecnicas.incorporate',
            array_merge(
                $this->recepcionTecnicaService->detailData($recepcionTecnica, request()->query('return_to')),
                [
                    'restoredSelectedEquipo' => app(MesaTecnicaService::class)->selectedEquipo(request()->user(), old('equipo_id')),
                    'equipmentStates' => array_values(array_filter(
                        Equipo::ESTADOS,
                        fn (string $estado): bool => $estado !== Equipo::ESTADO_EN_MANTENIMIENTO
                    )),
                ],
            )
        );
    }

    public function storeIncorporation(
        StoreRecepcionTecnicaIncorporacionRequest $request,
        RecepcionTecnica $recepcionTecnica
    ): RedirectResponse {
        $this->authorize('incorporate', $recepcionTecnica);

        try {
            $recepcionTecnica = $this->recepcionTecnicaService->incorporate(
                $request->user(),
                $recepcionTecnica,
                $request->validated()
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->friendlyErrorRedirect($exception);
        }

        $status = $recepcionTecnica->equipo_creado_id !== null
            ? 'El equipo fue incorporado al sistema y quedo vinculado al ticket de ingreso.'
            : 'El equipo existente quedo vinculado correctamente al ticket de ingreso.';

        return redirect()
            ->route('mesa-tecnica.recepciones-tecnicas.show', $recepcionTecnica)
            ->with('status', $status);
    }

    public function updateStatus(
        UpdateRecepcionTecnicaStatusRequest $request,
        RecepcionTecnica $recepcionTecnica
    ): RedirectResponse {
        $this->authorize('updateStatus', $recepcionTecnica);

        try {
            $recepcionTecnica = $this->recepcionTecnicaService->updateStatus(
                $request->user(),
                $recepcionTecnica,
                $request->validated()
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->friendlyErrorRedirect($exception, false);
        }

        return $this->redirectToShow(
            $recepcionTecnica,
            'Seguimiento del ingreso tecnico actualizado correctamente.',
            $request->input('return_to')
        );
    }

    public function close(
        CloseRecepcionTecnicaRequest $request,
        RecepcionTecnica $recepcionTecnica
    ): RedirectResponse {
        $this->authorize('close', $recepcionTecnica);

        try {
            $recepcionTecnica = $this->recepcionTecnicaService->close(
                $request->user(),
                $recepcionTecnica,
                $request->validated()
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->friendlyErrorRedirect($exception, false);
        }

        return $this->redirectToShow(
            $recepcionTecnica,
            'Ingreso tecnico cerrado correctamente y agregado al historial de mantenimiento.',
            $request->input('return_to')
        );
    }

    private function redirectToShow(
        RecepcionTecnica $recepcionTecnica,
        string $statusMessage,
        ?string $returnTo = null
    ): RedirectResponse {
        $parameters = ['recepcionTecnica' => $recepcionTecnica];
        $sanitizedReturnTo = $this->recepcionTecnicaService->sanitizeReturnUrl($returnTo);

        if ($sanitizedReturnTo !== null) {
            $parameters['return_to'] = $sanitizedReturnTo;
        }

        return redirect()
            ->route('mesa-tecnica.recepciones-tecnicas.show', $parameters)
            ->with('status', $statusMessage);
    }
}
