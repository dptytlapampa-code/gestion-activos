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

    public function index(): View
    {
        $offices = Office::query()
            ->with(['service.institution'])
            ->orderBy('nombre')
            ->paginate(10);

        return view('offices.index', [
            'offices' => $offices,
        ]);
    }

    public function create(): View
    {
        $services = Service::query()
            ->with('institution')
            ->orderBy('nombre')
            ->get();

        return view('offices.create', [
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
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

    public function edit(Office $office): View
    {
        $services = Service::query()
            ->with('institution')
            ->orderBy('nombre')
            ->get();

        return view('offices.edit', [
            'office' => $office,
            'services' => $services,
        ]);
    }

    public function update(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
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

    public function destroy(Office $office): RedirectResponse
    {
        $office->delete();

        return redirect()
            ->route('offices.index')
            ->with('status', 'Oficina eliminada correctamente.');
    }
}
