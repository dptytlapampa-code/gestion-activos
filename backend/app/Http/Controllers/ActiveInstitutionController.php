<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use App\Services\Auditing\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveInstitutionController extends Controller
{
    public function __construct(
        private readonly ActiveInstitutionContext $activeInstitutionContext,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
        ], [
            'institution_id.required' => 'Debe seleccionar una institucion habilitada para continuar.',
            'institution_id.exists' => 'La institucion seleccionada no es valida.',
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $previousInstitution = $this->activeInstitutionContext->activeInstitution($user, $request->session());
        $institution = $this->activeInstitutionContext->set($user, (int) $validated['institution_id'], $request->session());

        if ($institution === null) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'No tiene permisos para operar en la institucion seleccionada.');
        }

        if (($previousInstitution?->id ?? null) !== $institution->id) {
            $this->auditLogService->record([
                'user' => $user,
                'institution_id' => $institution->id,
                'module' => 'sesion',
                'action' => 'institucion_activa_actualizada',
                'entity_type' => 'usuario',
                'entity_id' => $user->id,
                'summary' => sprintf(
                    'El usuario %s cambio su institucion activa a %s.',
                    $user->name,
                    $institution->nombre
                ),
                'before' => [
                    'institucion_activa' => $previousInstitution?->nombre ?? 'Sin institucion activa',
                ],
                'after' => [
                    'institucion_activa' => $institution->nombre,
                ],
                'metadata' => [
                    'details' => [
                        'institucion_activa_id' => $institution->id,
                        'institucion_principal_id' => $user->institution_id,
                    ],
                ],
                'level' => AuditLog::LEVEL_INFO,
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', sprintf('Ahora esta operando en %s.', $institution->nombre));
    }
}
