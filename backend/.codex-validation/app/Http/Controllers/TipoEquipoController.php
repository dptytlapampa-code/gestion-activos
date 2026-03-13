<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoEquipoRequest;
use App\Http\Requests\UpdateTipoEquipoRequest;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\TipoEquipoImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class TipoEquipoController extends Controller
{
    public function __construct(private readonly TipoEquipoImageService $tipoEquipoImageService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeRead($request);

        $tipos_equipos = TipoEquipo::query()
            ->when(
                $request->filled('q'),
                fn ($query) => $query->where('nombre', 'ilike', '%'.$request->string('q').'%')
            )
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('tipos_equipos.index', [
            'tipos_equipos' => $tipos_equipos,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeWrite($request);

        return view('tipos_equipos.create');
    }

    public function store(StoreTipoEquipoRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['nombre', 'descripcion']);
        $storedImagePath = null;

        try {
            if ($request->hasFile('imagen_png')) {
                $storedImagePath = $this->tipoEquipoImageService->storeUploadedImage($request->file('imagen_png'));
                $data['image_path'] = $storedImagePath;
            }

            TipoEquipo::query()->create($data);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($storedImagePath !== null) {
                $this->tipoEquipoImageService->deleteImage($storedImagePath);
            }

            report($exception);

            return back()
                ->withInput()
                ->with('error', 'No fue posible guardar el tipo de equipo. Verifique la imagen e intente nuevamente.');
        }

        return redirect()
            ->route('tipos-equipos.index')
            ->with('status', 'Tipo de equipo creado correctamente.');
    }

    public function show(Request $request, TipoEquipo $tipo_equipo): View
    {
        $this->authorizeRead($request);

        return view('tipos_equipos.show', [
            'tipo_equipo' => $tipo_equipo->loadCount('equipos'),
        ]);
    }

    public function edit(Request $request, TipoEquipo $tipo_equipo): View
    {
        $this->authorizeWrite($request);

        return view('tipos_equipos.edit', [
            'tipo_equipo' => $tipo_equipo,
        ]);
    }

    public function update(UpdateTipoEquipoRequest $request, TipoEquipo $tipo_equipo): RedirectResponse
    {
        $data = $request->safe()->only(['nombre', 'descripcion']);
        $removeCurrentImage = $request->boolean('remove_imagen_png');
        $currentImagePath = $tipo_equipo->image_path;
        $newImagePath = null;

        try {
            if ($request->hasFile('imagen_png')) {
                $newImagePath = $this->tipoEquipoImageService->storeUploadedImage($request->file('imagen_png'));
                $data['image_path'] = $newImagePath;
            } elseif ($removeCurrentImage) {
                $data['image_path'] = null;
            }

            $tipo_equipo->update($data);

            if ($newImagePath !== null) {
                $this->tipoEquipoImageService->deleteImage($currentImagePath, $newImagePath);
            } elseif ($removeCurrentImage) {
                $this->tipoEquipoImageService->deleteImage($currentImagePath);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                $this->tipoEquipoImageService->deleteImage($newImagePath);
            }

            report($exception);

            return back()
                ->withInput()
                ->with('error', 'No fue posible actualizar el tipo de equipo. Verifique la imagen e intente nuevamente.');
        }

        return redirect()
            ->route('tipos-equipos.index')
            ->with('status', 'Tipo de equipo actualizado correctamente.');
    }

    public function destroy(Request $request, TipoEquipo $tipo_equipo): RedirectResponse
    {
        $this->authorizeWrite($request);

        $imagePath = $tipo_equipo->image_path;

        $tipo_equipo->delete();
        $this->tipoEquipoImageService->deleteImage($imagePath);

        return redirect()
            ->route('tipos-equipos.index')
            ->with('status', 'Tipo de equipo eliminado correctamente.');
    }

    private function authorizeRead(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN, User::ROLE_TECNICO, User::ROLE_VIEWER),
            403
        );
    }

    private function authorizeWrite(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole(User::ROLE_SUPERADMIN, User::ROLE_ADMIN),
            403
        );
    }
}