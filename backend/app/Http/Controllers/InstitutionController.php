<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:superadmin,admin,tecnico')->only(['index', 'show']);
        $this->middleware('role:superadmin,admin')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('role:superadmin')->only('destroy');
    }

    public function index(): View
    {
        $institutions = Institution::withCount('services')
            ->orderBy('name')
            ->paginate(15);

        return view('institutions.index', compact('institutions'));
    }

    public function create(): View
    {
        return view('institutions.create');
    }

    public function store(StoreInstitutionRequest $request): RedirectResponse
    {
        $this->validateUniqueServiceNames($request->input('services', []));

        DB::transaction(function () use ($request) {
            $institution = Institution::create([
                'name' => $request->string('name')->trim(),
                'code' => $request->string('code')->trim()->value(),
                'active' => true,
            ]);

            foreach ($request->input('services') as $serviceInput) {
                $service = $institution->services()->create([
                    'name' => trim($serviceInput['name']),
                    'active' => true,
                ]);

                $this->validateUniqueOfficeNames($serviceInput['offices'] ?? [], $service->id);

                foreach ($serviceInput['offices'] as $officeInput) {
                    $service->offices()->create([
                        'name' => trim($officeInput['name']),
                        'floor' => isset($officeInput['floor']) ? trim($officeInput['floor']) : null,
                        'active' => true,
                    ]);
                }
            }
        });

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institución creada correctamente.');
    }

    public function show(int $institution_id): View
    {
        $institution = Institution::with(['services.offices'])
            ->findOrFail($institution_id);

        return view('institutions.show', compact('institution'));
    }

    public function edit(int $institution_id): View
    {
        $institution = Institution::with(['services.offices'])
            ->findOrFail($institution_id);

        return view('institutions.edit', compact('institution'));
    }

    public function update(UpdateInstitutionRequest $request, int $institution_id): RedirectResponse
    {
        $institution = Institution::with(['services.offices'])->findOrFail($institution_id);

        $this->validateUniqueServiceNames($request->input('services', []), $institution->id);

        DB::transaction(function () use ($request, $institution) {
            $institution->update([
                'name' => $request->string('name')->trim(),
                'code' => $request->string('code')->trim()->value(),
            ]);

            foreach ($request->input('services') as $serviceInput) {
                $service = $this->resolveService($institution, $serviceInput);

                $service->update([
                    'name' => trim($serviceInput['name']),
                ]);

                $this->validateUniqueOfficeNames($serviceInput['offices'] ?? [], $service->id);

                foreach ($serviceInput['offices'] as $officeInput) {
                    $office = $this->resolveOffice($service, $officeInput);

                    $office->update([
                        'name' => trim($officeInput['name']),
                        'floor' => isset($officeInput['floor']) ? trim($officeInput['floor']) : null,
                    ]);
                }
            }
        });

        return redirect()
            ->route('institutions.edit', $institution_id)
            ->with('status', 'Institución actualizada correctamente.');
    }

    public function destroy(int $institution_id): RedirectResponse
    {
        $institution = Institution::findOrFail($institution_id);
        $institution->update(['active' => false]);

        return redirect()
            ->route('institutions.index')
            ->with('status', 'Institución desactivada correctamente.');
    }

    private function validateUniqueServiceNames(array $services, ?int $institutionId = null): void
    {
        $names = [];

        foreach ($services as $service) {
            $name = mb_strtolower(trim($service['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            if (in_array($name, $names, true)) {
                throw ValidationException::withMessages([
                    'services' => 'No se permiten servicios duplicados dentro de la institución.',
                ]);
            }

            $names[] = $name;

            if ($institutionId) {
                $exists = Service::query()
                    ->where('institution_id', $institutionId)
                    ->whereRaw('LOWER(name) = ?', [$name])
                    ->when(isset($service['id']), function ($query) use ($service) {
                        $query->where('id', '!=', $service['id']);
                    })
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'services' => 'Ya existe un servicio con el mismo nombre en esta institución.',
                    ]);
                }
            }
        }
    }

    private function validateUniqueOfficeNames(array $offices, int $serviceId): void
    {
        $names = [];

        foreach ($offices as $office) {
            $name = mb_strtolower(trim($office['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            if (in_array($name, $names, true)) {
                throw ValidationException::withMessages([
                    'services' => 'No se permiten oficinas duplicadas dentro de un mismo servicio.',
                ]);
            }

            $names[] = $name;

            $exists = Office::query()
                ->where('service_id', $serviceId)
                ->whereRaw('LOWER(name) = ?', [$name])
                ->when(isset($office['id']), function ($query) use ($office) {
                    $query->where('id', '!=', $office['id']);
                })
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'services' => 'Ya existe una oficina con el mismo nombre en este servicio.',
                ]);
            }
        }
    }

    private function resolveService(Institution $institution, array $serviceInput): Service
    {
        if (isset($serviceInput['id'])) {
            $service = $institution->services()->whereKey($serviceInput['id'])->first();

            if (!$service) {
                throw ValidationException::withMessages([
                    'services' => 'Servicio inválido para la institución seleccionada.',
                ]);
            }

            return $service;
        }

        return $institution->services()->create([
            'name' => trim($serviceInput['name']),
            'active' => true,
        ]);
    }

    private function resolveOffice(Service $service, array $officeInput): Office
    {
        if (isset($officeInput['id'])) {
            $office = $service->offices()->whereKey($officeInput['id'])->first();

            if (!$office) {
                throw ValidationException::withMessages([
                    'services' => 'Oficina inválida para el servicio seleccionado.',
                ]);
            }

            return $office;
        }

        return $service->offices()->create([
            'name' => trim($officeInput['name']),
            'floor' => isset($officeInput['floor']) ? trim($officeInput['floor']) : null,
            'active' => true,
        ]);
    }
}
