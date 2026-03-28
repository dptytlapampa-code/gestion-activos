@csrf
@php
    $selectedAdditionalInstitutionIds = collect(old(
        'accessible_institution_ids',
        isset($user) ? $user->permittedInstitutions->pluck('id')->all() : []
    ))->map(fn ($id) => (string) $id);
    $selectedRole = old('role', $user->role ?? '');
@endphp

<div
    x-data="{
        role: @js($selectedRole),
        superadminRole: @js(\App\Models\User::ROLE_SUPERADMIN),
        centralInstitutionId: @js((string) ($centralInstitution->id ?? '')),
    }"
    class="grid gap-4 md:grid-cols-2"
>
    <div class="app-subcard p-4 md:col-span-2">
        <p class="text-sm font-semibold text-slate-900">Contexto institucional del usuario</p>
        <p class="mt-2 text-sm text-slate-600">
            La institucion principal define la pertenencia base del usuario y se toma como institucion activa al iniciar sesion.
            Las instituciones habilitadas solo permiten cambiar el contexto de trabajo durante la sesion; no mezclan automaticamente la operacion diaria.
        </p>
        <p x-cloak x-show="role === superadminRole" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
            Los superadmins pertenecen nativamente a {{ $centralInstitution->nombre ?? 'Nivel Central' }} y operan con alcance global del sistema.
        </p>
    </div>

    <div>
        <label class="text-sm font-medium text-slate-700" for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name', $user->name ?? '') }}" class="form-control @error('name') form-control-error @enderror" />
        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm font-medium text-slate-700" for="email">Email</label>
        <input id="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-control @error('email') form-control-error @enderror" />
        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    @if (! isset($user))
        <div>
            <label class="text-sm font-medium text-slate-700" for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') form-control-error @enderror" />
            @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        <label class="text-sm font-medium text-slate-700" for="role">Rol</label>
        <select id="role" name="role" x-model="role" class="form-control @error('role') form-control-error @enderror">
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ $role }}</option>
            @endforeach
        </select>
        @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm font-medium text-slate-700" for="institution_id">Institucion principal</label>
        <input x-cloak x-show="role === superadminRole" type="hidden" name="institution_id" :value="centralInstitutionId">
        <select
            id="institution_id"
            name="institution_id"
            :disabled="role === superadminRole"
            class="form-control @error('institution_id') form-control-error @enderror"
        >
            <option value="">Sin institucion</option>
            @foreach ($institutions as $institution)
                <option value="{{ $institution->id }}" @selected((string) old('institution_id', $user->institution_id ?? '') === (string) $institution->id)>{{ $institution->nombre }}</option>
            @endforeach
        </select>
        <p x-cloak x-show="role !== superadminRole" class="mt-1 text-xs text-slate-500">Sera la institucion activa inicial cada vez que el usuario ingrese al sistema.</p>
        <p x-cloak x-show="role === superadminRole" class="mt-1 text-xs text-slate-500">
            Se fija automaticamente en {{ $centralInstitution->nombre ?? 'Nivel Central' }}.
        </p>
        @error('institution_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-medium text-slate-700" for="accessible_institution_ids">Instituciones habilitadas adicionales (opcional)</label>
        <p x-cloak x-show="role !== superadminRole" class="mt-1 text-xs text-slate-500">Permiten cambiar la institucion activa durante la sesion. No combinan automaticamente los datos operativos.</p>
        <p x-cloak x-show="role === superadminRole" class="mt-1 text-xs text-slate-500">No son necesarias para superadmins porque su alcance ya es global desde {{ $centralInstitution->nombre ?? 'Nivel Central' }}.</p>
        <div id="accessible_institution_ids" class="app-subcard mt-2 grid gap-2 p-3 md:grid-cols-2">
            @foreach ($institutions as $institution)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="accessible_institution_ids[]"
                        value="{{ $institution->id }}"
                        :disabled="role === superadminRole"
                        @checked($selectedAdditionalInstitutionIds->contains((string) $institution->id))
                    >
                    <span>{{ $institution->nombre }}</span>
                </label>
            @endforeach
        </div>
        @error('accessible_institution_ids')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        @error('accessible_institution_ids.*')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>

<button class="btn btn-primary mt-4">Guardar</button>
