<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use App\Services\Auditing\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ActiveInstitutionContext $activeInstitutionContext,
    ) {}

    public function create(): \Illuminate\View\View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $matchedUser = User::query()->where('email', $credentials['email'])->first();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->auditLogService->record([
                'user_id' => $matchedUser?->id,
                'institution_id' => $matchedUser?->institution_id,
                'module' => 'auth',
                'action' => 'login_failed',
                'entity_type' => 'usuario',
                'entity_id' => $matchedUser?->id,
                'summary' => $matchedUser !== null
                    ? sprintf('Se rechazo el acceso del usuario %s por credenciales invalidas.', $matchedUser->name)
                    : 'Se rechazo un intento de acceso por credenciales invalidas.',
                'metadata' => [
                    'details' => [
                        'email' => $credentials['email'],
                        'motivo' => 'Credenciales invalidas',
                    ],
                ],
                'level' => AuditLog::LEVEL_ERROR,
                'is_critical' => true,
            ]);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if (! (bool) auth()->user()?->is_active) {
            $user = auth()->user();

            $this->auditLogService->record([
                'user_id' => $user?->id,
                'institution_id' => $user?->institution_id,
                'module' => 'auth',
                'action' => 'login_failed',
                'entity_type' => 'usuario',
                'entity_id' => $user?->id,
                'summary' => sprintf('Se bloqueo el acceso del usuario %s porque su cuenta esta inactiva.', $user?->name ?? 'sin identificar'),
                'metadata' => [
                    'details' => [
                        'email' => $credentials['email'],
                        'motivo' => 'Cuenta inactiva',
                    ],
                ],
                'level' => AuditLog::LEVEL_ERROR,
                'is_critical' => true,
            ]);

            Auth::logout();

            throw ValidationException::withMessages(['email' => 'Usuario inactivo.']);
        }

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = auth()->user();

        if ($user !== null) {
            $defaultActiveInstitutionId = $this->activeInstitutionContext->defaultId($user);

            if ($defaultActiveInstitutionId !== null) {
                $this->activeInstitutionContext->set($user, $defaultActiveInstitutionId, $request->session());
            } else {
                $this->activeInstitutionContext->forget($request->session());
            }
        }

        $this->auditLogService->record([
            'user_id' => $user?->id,
            'institution_id' => $user?->institution_id,
            'module' => 'auth',
            'action' => 'login',
            'entity_type' => 'usuario',
            'entity_id' => $user?->id,
            'summary' => sprintf('Inicio de sesion del usuario %s.', $user?->name ?? 'sin identificar'),
            'metadata' => [
                'details' => [
                    'email' => $user?->email,
                ],
            ],
            'level' => AuditLog::LEVEL_INFO,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $this->auditLogService->record([
                'user_id' => $user->id,
                'institution_id' => $user->institution_id,
                'module' => 'auth',
                'action' => 'logout',
                'entity_type' => 'usuario',
                'entity_id' => $user->id,
                'summary' => sprintf('Cierre de sesion del usuario %s.', $user->name),
                'metadata' => [
                    'details' => [
                        'email' => $user->email,
                    ],
                ],
            ]);
        }

        Auth::logout();
        $this->activeInstitutionContext->forget($request->session());

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
