<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        return view('institutions.create', [
            'tipos' => Institution::TIPOS,
            'estados' => Institution::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:20', Rule::unique('institutions', 'codigo')],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('institutions', 'nombre')],
            'tipo' => ['required', Rule::in(Institution::TIPOS)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'localidad' => ['nullable', 'string', 'max:150'],
            'provincia' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(Institution::ESTADOS)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        Institution::create($validated);

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institucion creada correctamente.');
    }

    public function edit(Institution $institution): View
    {
        return view('institutions.edit', [
            'institution' => $institution,
            'tipos' => Institution::TIPOS,
            'estados' => Institution::ESTADOS,
        ]);
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('institutions', 'codigo')->ignore($institution->id),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('institutions', 'nombre')->ignore($institution->id),
            ],
            'tipo' => ['required', Rule::in(Institution::TIPOS)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'localidad' => ['nullable', 'string', 'max:150'],
            'provincia' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(Institution::ESTADOS)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($institution->codigo !== null) {
            $validated['codigo'] = $institution->codigo;
        }

        $institution->update($validated);

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institucion actualizada correctamente.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        $institution->delete();

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institucion eliminada correctamente.');
    }
}
