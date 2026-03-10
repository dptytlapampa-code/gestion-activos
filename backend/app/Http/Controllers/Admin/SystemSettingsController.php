<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function __construct(private readonly SystemSettingsService $settingsService)
    {
        $this->middleware('can:manage-system-settings');
    }

    public function index(): View
    {
        return view('admin.configuracion.general', [
            'settings' => $this->settingsService->getCurrentSettings(),
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settingsService->update(
            $validated,
            $request->file('logo_institucional') ?? $request->file('logo'),
            $request->file('logo_pdf'),
        );

        return redirect()
            ->route('admin.configuracion.general.edit')
            ->with('status', 'La configuracion general se actualizo correctamente.');
    }
}
