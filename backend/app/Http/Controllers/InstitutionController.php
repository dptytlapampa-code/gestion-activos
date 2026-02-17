<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:' . User::ROLE_SUPERADMIN . ',' . User::ROLE_ADMIN])
            ->only('index');
        $this->middleware(['auth', 'role:' . User::ROLE_SUPERADMIN])
            ->except('index');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $institutions = Institution::query()
            ->when(
                $user !== null && ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->where('id', $user->institution_id)
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('institutions.index', [
            'institutions' => $institutions,
        ]);
    }

    public function create(): View
    {
        return view('institutions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'unique:institutions,codigo'],
            'nombre' => ['required', 'string', 'max:255', 'unique:institutions,nombre'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        Institution::create($validated);

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institución creada correctamente.');
    }

    public function edit(Institution $institution): View
    {
        return view('institutions.edit', [
            'institution' => $institution,
        ]);
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:255', 'unique:institutions,nombre,' . $institution->id],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ];

        if ($institution->codigo === null) {
            $rules['codigo'] = ['required', 'string', 'max:20', 'unique:institutions,codigo'];
        }

        $validated = $request->validate($rules);

        if ($institution->codigo !== null) {
            unset($validated['codigo']);
        }

        $institution->update($validated);

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institución actualizada correctamente.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        $institution->delete();

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institución eliminada correctamente.');
    }
}
