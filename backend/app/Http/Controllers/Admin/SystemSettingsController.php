<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SystemSettingsController extends Controller
{
    public function __construct(private readonly SystemSettingsService $settingsService)
    {
        $this->middleware('can:manage-system-settings');
    }

    public function index(): View
    {
        return view('admin.configuracion.general');
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        try {
            $this->settingsService->update($request->validated(), $request->file('logo'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'No fue posible guardar la configuración en este momento.',
                ]);
        }

        return redirect()
            ->route('admin.configuracion.general.edit')
            ->with('status', 'Configuración actualizada correctamente.');
    }
}
