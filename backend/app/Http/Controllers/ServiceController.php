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

        $services = Service::query()
            ->with('institution')
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('institution_id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('services.index', [
            'services' => $services,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $institutions = Institution::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->get();

        return view('services.create', [
            'institutions' => $institutions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'institution_id' => [
                'required',
                'integer',
                Rule::exists('institutions', 'id')->when(
                    $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                    fn ($query) => $query->where('id', $user->institution_id)
                ),
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
        $user = $request->user();

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $service->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $institutions = Institution::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->get();

        return view('services.edit', [
            'service' => $service,
            'institutions' => $institutions,
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $service->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $validated = $request->validate([
            'institution_id' => [
                'required',
                'integer',
                Rule::exists('institutions', 'id')->when(
                    $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                    fn ($query) => $query->where('id', $user->institution_id)
                ),
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
        $user = $request->user();

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $service->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('status', 'Servicio eliminado correctamente.');
    }
}
