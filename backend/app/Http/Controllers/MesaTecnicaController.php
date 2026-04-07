<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\User;
use App\Services\MesaTecnicaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MesaTecnicaController extends Controller
{
    public function __construct(private readonly MesaTecnicaService $mesaTecnicaService) {}

    public function index(Request $request): View
    {
        $this->authorize('mesa_tecnica_access');

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return view('mesa-tecnica.index', $this->mesaTecnicaService->dashboard($user));
    }

    public function label(Equipo $equipo): View
    {
        $this->authorize('mesa_tecnica_access');
        $this->authorize('view', $equipo);

        return view('mesa-tecnica.label', $this->mesaTecnicaService->labelData($equipo));
    }
}
