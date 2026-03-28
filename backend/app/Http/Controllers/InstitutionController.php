<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use App\Support\Listings\ListingState;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LogicException;

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
        $listing = ListingState::fromRequest($request);
        $institutions = Institution::query()
            ->visibleToUser($request->user())
            ->searchIndex($listing->search)
            ->orderBy('nombre')
            ->paginate($listing->perPage)
            ->withQueryString();

        return view('institutions.index', [
            'institutions' => $institutions,
            'listing' => $listing,
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
            'codigo_institucional' => ['required', 'string', 'max:20', Rule::unique('institutions', 'codigo')],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(Institution::TIPOS)],
            'estado' => ['required', Rule::in(Institution::ESTADOS)],
            'provincia' => ['nullable', 'string', 'max:150'],
            'localidad' => ['nullable', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        Institution::create($this->mapToDatabaseFields($validated));

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
            'codigo_institucional' => [
                'required',
                'string',
                'max:20',
                Rule::unique('institutions', 'codigo')->ignore($institution->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(Institution::TIPOS)],
            'estado' => ['required', Rule::in(Institution::ESTADOS)],
            'provincia' => ['nullable', 'string', 'max:150'],
            'localidad' => ['nullable', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = $this->mapToDatabaseFields($validated);

        if ($institution->codigo !== null) {
            $payload['codigo'] = $institution->codigo;
        }

        $institution->update($payload);

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institucion actualizada correctamente.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        try {
            $institution->delete();

            return redirect()
                ->route('institutions.index')
                ->with('status', 'Institucion eliminada correctamente.');
        } catch (LogicException $e) {
            return redirect()
                ->route('institutions.index')
                ->with('error', $e->getMessage());
        } catch (QueryException $e) {
            return redirect()
                ->route('institutions.index')
                ->with('error', 'No se puede eliminar esta institucion porque tiene equipos asociados. Primero reasigne o elimine los equipos.');
        }
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function mapToDatabaseFields(array $validated): array
    {
        return [
            'codigo' => $validated['codigo_institucional'],
            'nombre' => $validated['nombre'],
            'provincia' => $validated['provincia'] ?? null,
            'localidad' => $validated['localidad'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'email' => $validated['email'] ?? null,
            'responsable' => $validated['responsable'] ?? null,
            'tipo' => $validated['tipo'],
            'estado' => $validated['estado'],
            'descripcion' => $validated['descripcion'] ?? null,
        ];
    }
}
