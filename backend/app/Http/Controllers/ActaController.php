<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\AuditLog;
use App\Models\Equipo;
use App\Models\Institution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Acta::class);

        $user = $request->user();
        $validated = $request->validate([
            'tipo' => ['nullable', Rule::in(Acta::TIPOS)],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $actas = Acta::query()
            ->withCount('equipos')
            ->with('creator:id,name')
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn (Builder $query) => $query->where('institution_id', $user->institution_id)
            )
            ->when($validated['tipo'] ?? null, fn (Builder $query, string $tipo) => $query->where('tipo', $tipo))
            ->when($validated['fecha_desde'] ?? null, fn (Builder $query, string $fecha_desde) => $query->whereDate('fecha', '>=', $fecha_desde))
            ->when($validated['fecha_hasta'] ?? null, fn (Builder $query, string $fecha_hasta) => $query->whereDate('fecha', '<=', $fecha_hasta))
            ->latest('fecha')
            ->paginate(15)
            ->withQueryString();

        return view('actas.index', [
            'actas' => $actas,
            'tipos' => Acta::TIPOS,
            'filters' => $validated,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Acta::class);

        $user = $request->user();
        $institutions = $user->hasRole(User::ROLE_SUPERADMIN)
            ? Institution::query()->orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        return view('actas.create', [
            'tipos' => Acta::TIPOS,
            'institutions' => $institutions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Acta::class);

        $user = $request->user();
        $validated = $request->validate([
            'institution_id' => [
                Rule::requiredIf($user->hasRole(User::ROLE_SUPERADMIN)),
                'nullable',
                'integer',
                'exists:institutions,id',
            ],
            'tipo' => ['required', Rule::in(Acta::TIPOS)],
            'fecha' => ['required', 'date'],
            'receptor_nombre' => ['required', 'string', 'max:255'],
            'receptor_dni' => ['nullable', 'string', 'max:50'],
            'receptor_cargo' => ['nullable', 'string', 'max:255'],
            'receptor_dependencia' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'equipos' => ['required', 'array', 'min:1'],
            'equipos.*.equipo_id' => ['required', 'integer', 'distinct', 'exists:equipos,id'],
            'equipos.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'equipos.*.accesorios' => ['nullable', 'string'],
        ]);

        $institutionId = $user->hasRole(User::ROLE_SUPERADMIN)
            ? (int) $validated['institution_id']
            : (int) $user->institution_id;

        if ($institutionId <= 0) {
            return back()->withErrors(['institution_id' => 'Debe seleccionar una institución válida.'])->withInput();
        }

        $equipoIds = collect($validated['equipos'])->pluck('equipo_id')->map(fn ($id) => (int) $id);

        $equipos = Equipo::query()
            ->whereIn('id', $equipoIds)
            ->with(['oficina.service'])
            ->get();

        if ($equipos->count() !== $equipoIds->count()) {
            return back()->withErrors(['equipos' => 'Uno o más equipos seleccionados no existen.'])->withInput();
        }

        $scopeFail = $equipos->contains(
            fn (Equipo $equipo): bool => (int) $equipo->oficina?->service?->institution_id !== $institutionId
        );

        if ($scopeFail) {
            return back()->withErrors(['equipos' => 'Todos los equipos deben pertenecer a la misma institución del acta.'])->withInput();
        }

        $acta = DB::transaction(function () use ($validated, $user, $institutionId): Acta {
            $acta = Acta::query()->create([
                'institution_id' => $institutionId,
                'tipo' => $validated['tipo'],
                'fecha' => $validated['fecha'],
                'receptor_nombre' => $validated['receptor_nombre'],
                'receptor_dni' => $validated['receptor_dni'] ?? null,
                'receptor_cargo' => $validated['receptor_cargo'] ?? null,
                'receptor_dependencia' => $validated['receptor_dependencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'created_by' => $user->id,
            ]);

            $pivotPayload = collect($validated['equipos'])
                ->mapWithKeys(fn (array $item): array => [
                    (int) $item['equipo_id'] => [
                        'cantidad' => (int) $item['cantidad'],
                        'accesorios' => $item['accesorios'] ?? null,
                    ],
                ])
                ->all();

            $acta->equipos()->sync($pivotPayload);

            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => 'create',
                'auditable_type' => 'acta_equipo',
                'auditable_id' => $acta->id,
                'before' => null,
                'after' => ['equipos' => $pivotPayload],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $acta->load(['institution', 'creator', 'equipos.tipoEquipo']);

            $pdfBinary = Pdf::loadView("actas.pdf.$acta->tipo", compact('acta'))
                ->setPaper('a4')
                ->output();

            $path = sprintf('documents/%s/%s.pdf', now()->format('Y/m'), strtolower($acta->codigo));
            Storage::put($path, $pdfBinary);

            $acta->documents()->create([
                'uploaded_by' => $user->id,
                'type' => 'acta',
                'note' => sprintf('Acta %s %s', $acta->tipo, $acta->codigo),
                'file_path' => $path,
                'original_name' => $acta->codigo.'.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($pdfBinary),
            ]);

            return $acta;
        });

        return redirect()->route('actas.show', $acta)->with('status', 'Acta generada correctamente.');
    }

    public function show(Acta $acta)
    {
        $this->authorize('view', $acta);

        $acta->load([
            'institution',
            'creator:id,name',
            'equipos.tipoEquipo',
            'equipos.oficina.service',
            'documents.uploadedBy:id,name',
        ]);

        return view('actas.show', ['acta' => $acta]);
    }

    public function descargar($id): BinaryFileResponse
    {
        $acta = Acta::with(['institution', 'equipos.tipoEquipo', 'creator'])->findOrFail($id);
        $this->authorize('view', $acta);

        $pdf = Pdf::loadView("actas.pdf.$acta->tipo", compact('acta'))
            ->setPaper('a4');

        return $pdf->download($acta->codigo.'.pdf');
    }
}
