<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-users');
    }

    public function index(Request $request): View
    {
        $users = User::query()->with('institution')->orderBy('name')->paginate(15);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => User::ROLES,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['institutions' => Institution::orderBy('nombre')->get(), 'roles' => User::ROLES]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'institution_id' => $validated['role'] === User::ROLE_SUPERADMIN ? null : $validated['institution_id'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user, 'institutions' => Institution::orderBy('nombre')->get(), 'roles' => User::ROLES]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'institution_id' => $validated['role'] === User::ROLE_SUPERADMIN ? null : $validated['institution_id'],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', 'Estado de usuario actualizado.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make('123456')]);

        return back()->with('status', 'Contraseña reseteada a 123456.');
    }
}
