<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfficeController extends Controller
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

        $offices = Office::query()
            ->with(['service.institution'])
            ->when($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN), function ($query) use ($user): void {
                $query->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('institution_id', $user->institution_id));
            })
            ->orderBy('nombre')
            ->paginate(10);

        return view('offices.index', [
            'offices' => $offices,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $services = Service::query()
            ->with('institution')
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('institution_id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->get();

        return view('offices.create', [
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->when(
                    $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                    fn ($query) => $query->where('institution_id', $user->institution_id)
                ),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('offices', 'nombre')->where('service_id', $request->input('service_id')),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        Office::create($validated);

        return redirect()
            ->route('offices.index')
            ->with('status', 'Oficina creada correctamente.');
    }

    public function edit(Request $request, Office $office): View
    {
        $user = $request->user();

        $office->loadMissing('service');

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $office->service?->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $services = Service::query()
            ->with('institution')
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('institution_id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->get();

        return view('offices.edit', [
            'office' => $office,
            'services' => $services,
        ]);
    }

    public function update(Request $request, Office $office): RedirectResponse
    {
        $user = $request->user();

        $office->loadMissing('service');

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $office->service?->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $validated = $request->validate([
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->when(
                    $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                    fn ($query) => $query->where('institution_id', $user->institution_id)
                ),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('offices', 'nombre')
                    ->where('service_id', $request->input('service_id'))
                    ->ignore($office->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        $office->update($validated);

        return redirect()
            ->route('offices.index')
            ->with('status', 'Oficina actualizada correctamente.');
    }

    public function destroy(Request $request, Office $office): RedirectResponse
    {
        $user = $request->user();

        $office->loadMissing('service');

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $office->service?->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $office->delete();

        return redirect()
            ->route('offices.index')
            ->with('status', 'Oficina eliminada correctamente.');
    }
}
