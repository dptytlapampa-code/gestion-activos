<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing(['institution:id,nombre', 'permittedInstitutions:id,nombre']);

        return view('profile.edit', [
            'user' => $user,
            'activeInstitution' => $this->activeInstitution($user),
            'accessibleInstitutions' => $this->accessibleInstitutions($user),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Sus datos personales fueron actualizados.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Su contrasena fue actualizada correctamente.');
    }
}
