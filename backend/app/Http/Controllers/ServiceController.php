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

    public function index(): View
    {
        $services = Service::query()
            ->with('institution')
            ->orderBy('nombre')
            ->paginate(10);

        return view('services.index', [
            'services' => $services,
        ]);
    }

    public function create(): View
    {
        $institutions = Institution::query()
            ->orderBy('nombre')
            ->get();

        return view('services.create', [
            'institutions' => $institutions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
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

    public function edit(Service $service): View
    {
        $institutions = Institution::query()
            ->orderBy('nombre')
            ->get();

        return view('services.edit', [
            'service' => $service,
            'institutions' => $institutions,
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
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

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('status', 'Servicio eliminado correctamente.');
    }
}
