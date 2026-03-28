<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\User;
use App\Services\Auditing\AuditLogService;
use App\Services\InstitutionScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly InstitutionScopeService $institutionScopeService,
    ) {
        $this->middleware('can:manage-users');
    }

    public function index(Request $request): View
    {
        $users = User::query()->with(['institution', 'permittedInstitutions'])->orderBy('name')->paginate(15);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => User::ROLES,
        ]);
    }

    public function create(): View
    {
        $centralInstitution = $this->institutionScopeService->ensureCentralInstitution();

        return view('admin.users.create', [
            'institutions' => $this->availableInstitutions(),
            'roles' => User::ROLES,
            'centralInstitution' => $centralInstitution,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'institution_id' => $this->resolvedInstitutionIdForPayload($validated),
            'is_active' => true,
        ]);

        $this->syncInstitutionPermissions($user, $validated);
        $user->load(['institution:id,nombre', 'permittedInstitutions:id,nombre']);

        $snapshot = $this->userAuditSnapshot($user);

        $this->auditLogService->record([
            'user' => $request->user(),
            'institution_id' => $user->institution_id,
            'module' => 'usuarios',
            'action' => 'usuario_creado',
            'entity_type' => 'usuario',
            'entity_id' => $user->id,
            'summary' => sprintf('Se creo el usuario %s con rol %s.', $user->name, $snapshot['rol']),
            'after' => $snapshot,
            'metadata' => [
                'details' => $snapshot,
            ],
            'level' => AuditLog::LEVEL_CRITICAL,
            'is_critical' => true,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $centralInstitution = $this->institutionScopeService->ensureCentralInstitution();

        return view('admin.users.edit', [
            'user' => $user->load('permittedInstitutions'),
            'institutions' => $this->availableInstitutions(),
            'roles' => User::ROLES,
            'centralInstitution' => $centralInstitution,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $user->load(['institution:id,nombre', 'permittedInstitutions:id,nombre']);

        $before = $this->userAuditSnapshot($user);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'institution_id' => $this->resolvedInstitutionIdForPayload($validated),
        ]);

        $this->syncInstitutionPermissions($user, $validated);

        $user->refresh()->load(['institution:id,nombre', 'permittedInstitutions:id,nombre']);

        $after = $this->userAuditSnapshot($user);
        $changes = $this->auditLogService->diff($before, $after, [
            'nombre' => 'Nombre',
            'email' => 'Correo',
            'rol' => 'Rol',
            'institucion' => 'Institucion principal',
            'instituciones_habilitadas' => 'Instituciones habilitadas',
            'activo' => 'Estado',
        ]);

        if ($changes !== []) {
            $this->auditLogService->record([
                'user' => $request->user(),
                'institution_id' => $user->institution_id,
                'module' => 'usuarios',
                'action' => $this->containsPermissionChanges($changes)
                    ? 'usuario_permisos_actualizados'
                    : 'usuario_actualizado',
                'entity_type' => 'usuario',
                'entity_id' => $user->id,
                'summary' => $this->userUpdateSummary($user, $changes),
                'before' => $before,
                'after' => $after,
                'metadata' => [
                    'details' => $after,
                    'changes' => $changes,
                ],
                'level' => AuditLog::LEVEL_CRITICAL,
                'is_critical' => true,
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $before = $this->userAuditSnapshot($user->loadMissing(['institution:id,nombre', 'permittedInstitutions:id,nombre']));

        $user->update(['is_active' => ! $user->is_active]);
        $user->refresh()->load(['institution:id,nombre', 'permittedInstitutions:id,nombre']);

        $after = $this->userAuditSnapshot($user);

        $this->auditLogService->record([
            'user' => request()->user(),
            'institution_id' => $user->institution_id,
            'module' => 'usuarios',
            'action' => 'usuario_estado_actualizado',
            'entity_type' => 'usuario',
            'entity_id' => $user->id,
            'summary' => sprintf(
                'Se marco al usuario %s como %s.',
                $user->name,
                $user->is_active ? 'activo' : 'inactivo'
            ),
            'before' => $before,
            'after' => $after,
            'metadata' => [
                'details' => $after,
                'changes' => $this->auditLogService->diff($before, $after, ['activo' => 'Estado']),
            ],
            'level' => AuditLog::LEVEL_CRITICAL,
            'is_critical' => true,
        ]);

        return back()->with('status', 'Estado de usuario actualizado.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make('123456')]);

        $this->auditLogService->record([
            'user' => request()->user(),
            'institution_id' => $user->institution_id,
            'module' => 'usuarios',
            'action' => 'usuario_password_reiniciado',
            'entity_type' => 'usuario',
            'entity_id' => $user->id,
            'summary' => sprintf('Se reinicio la contrasena del usuario %s.', $user->name),
            'metadata' => [
                'details' => [
                    'usuario' => $user->name,
                    'correo' => $user->email,
                    'credencial_temporal' => '123456',
                ],
            ],
            'level' => AuditLog::LEVEL_CRITICAL,
            'is_critical' => true,
        ]);

        return back()->with('status', 'Contrasena reseteada a 123456.');
    }

    private function syncInstitutionPermissions(User $user, array $validated): void
    {
        if (($validated['role'] ?? null) === User::ROLE_SUPERADMIN) {
            $user->permittedInstitutions()->sync([]);

            return;
        }

        $mainInstitutionId = (int) ($validated['institution_id'] ?? 0);

        $permissionIds = collect($validated['accessible_institution_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn ($id): bool => $id > 0)
            ->reject(fn ($id): bool => $id === $mainInstitutionId)
            ->unique()
            ->values()
            ->all();

        $user->permittedInstitutions()->sync($permissionIds);
    }

    /**
     * @return array<string, string>
     */
    private function userAuditSnapshot(User $user): array
    {
        $institucionesHabilitadas = $user->permittedInstitutions
            ->pluck('nombre')
            ->prepend($user->institution?->nombre)
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        return [
            'nombre' => $user->name,
            'email' => $user->email,
            'rol' => $this->roleLabel($user->role),
            'institucion' => $user->institution?->nombre ?? 'Sin institucion principal',
            'instituciones_habilitadas' => $institucionesHabilitadas !== '' ? $institucionesHabilitadas : 'Sin accesos adicionales',
            'activo' => $user->is_active ? 'Activo' : 'Inactivo',
        ];
    }

    /**
     * @param  array<int, array{field:string,label:string,before:mixed,after:mixed}>  $changes
     */
    private function containsPermissionChanges(array $changes): bool
    {
        return collect($changes)->contains(fn (array $change): bool => in_array($change['field'], ['rol', 'institucion', 'instituciones_habilitadas', 'activo'], true));
    }

    /**
     * @param  array<int, array{field:string,label:string,before:mixed,after:mixed}>  $changes
     */
    private function userUpdateSummary(User $user, array $changes): string
    {
        $fields = collect($changes)->pluck('field');

        if ($fields->contains('rol') || $fields->contains('instituciones_habilitadas')) {
            return sprintf('Se actualizaron los permisos del usuario %s.', $user->name);
        }

        if ($fields->contains('activo')) {
            return sprintf('Se actualizo el estado del usuario %s.', $user->name);
        }

        return sprintf('Se actualizo la ficha del usuario %s.', $user->name);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            User::ROLE_SUPERADMIN => 'Superadmin',
            User::ROLE_ADMIN => 'Admin hospitalario',
            User::ROLE_TECNICO => 'Tecnico',
            User::ROLE_VIEWER => 'Viewer',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    private function availableInstitutions()
    {
        return Institution::query()
            ->orderByRaw(
                "case when scope_type = ? then 0 else 1 end",
                [Institution::SCOPE_GLOBAL]
            )
            ->orderBy('nombre')
            ->get();
    }

    private function resolvedInstitutionIdForPayload(array $validated): int
    {
        if (($validated['role'] ?? null) === User::ROLE_SUPERADMIN) {
            return $this->institutionScopeService->ensureCentralInstitution()->id;
        }

        return (int) $validated['institution_id'];
    }
}
