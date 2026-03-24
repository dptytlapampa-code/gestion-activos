<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            'role:' . User::ROLE_SUPERADMIN . ',' . User::ROLE_ADMIN . ',' . User::ROLE_TECNICO,
        ])->only('index');
        $this->middleware([
            'auth',
            'role:' . User::ROLE_SUPERADMIN . ',' . User::ROLE_ADMIN,
        ])->except('index');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $services = $this->applyGlobalAdministrationScope(
            Service::query()->with('institution'),
            'institution_id',
            $user
        )
            ->orderBy('institution_id')
            ->orderBy('nombre')
            ->paginate(10);

        return view('services.index', [
            'services' => $services,
        ]);
    }

    public function create(Request $request): View
    {
        return view('services.create', [
            'institutions' => $this->scopedInstitutions($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_id' => [
                'required',
                'integer',
                $this->scopedInstitutionExistsRule($request->user()),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'nombre')->where('institution_id', $request->input('institution_id')),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        Service::create($validated);

        return redirect()
            ->route('services.index')
            ->with('status', 'Servicio creado correctamente.');
    }

    public function edit(Request $request, Service $service): View
    {
        if (! $this->isWithinGlobalAdministrationScope($request->user(), (int) $service->institution_id)) {
            abort(403);
        }

        return view('services.edit', [
            'service' => $service,
            'institutions' => $this->scopedInstitutions($request->user()),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        if (! $this->isWithinGlobalAdministrationScope($request->user(), (int) $service->institution_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'institution_id' => [
                'required',
                'integer',
                $this->scopedInstitutionExistsRule($request->user()),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'nombre')
                    ->where('institution_id', $request->input('institution_id'))
                    ->ignore($service->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->update($validated);

        return redirect()
            ->route('services.index')
            ->with('status', 'Servicio actualizado correctamente.');
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        if (! $this->isWithinGlobalAdministrationScope($request->user(), (int) $service->institution_id)) {
            abort(403);
        }

        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('status', 'Servicio eliminado correctamente.');
    }

    private function scopedInstitutions(?User $user)
    {
        return $this->applyGlobalAdministrationScope(
            Institution::query(),
            'id',
            $user
        )
            ->orderBy('nombre')
            ->get();
    }

    private function scopedInstitutionExistsRule(?User $user): \Illuminate\Validation\Rules\Exists
    {
        $rule = Rule::exists('institutions', 'id');
        $scopeIds = $this->globalAdministrationScopeIds($user);

        if ($scopeIds === null) {
            return $rule;
        }

        return $rule->where(function ($query) use ($scopeIds): void {
            if ($scopeIds === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn('id', $scopeIds);
        });
    }
}
