<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('offices.create', [
            'institutions' => $this->scopedInstitutions($user),
            'services' => $this->scopedServices($user),
        ]);
    }

    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Office::create([
            'service_id' => $validated['service_id'],
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return redirect()
            ->route('offices.index')
            ->with('status', 'Oficina creada correctamente.');
    }

    public function edit(Request $request, Office $office): View
    {
        $user = $request->user();

        $office->loadMissing('service.institution');

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $office->service?->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        return view('offices.edit', [
            'office' => $office,
            'institutions' => $this->scopedInstitutions($user),
            'services' => $this->scopedServices($user),
        ]);
    }

    public function update(UpdateOfficeRequest $request, Office $office): RedirectResponse
    {
        $user = $request->user();

        $office->loadMissing('service');

        if ($user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN) && (int) $office->service?->institution_id !== (int) $user->institution_id) {
            abort(403);
        }

        $validated = $request->validated();

        $office->update([
            'service_id' => $validated['service_id'],
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

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

    private function scopedInstitutions(?User $user)
    {
        return Institution::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    private function scopedServices(?User $user)
    {
        return Service::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('institution_id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'institution_id']);
    }
}
