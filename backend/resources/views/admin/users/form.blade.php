@csrf
@php
    $selectedAdditionalInstitutionIds = collect(old(
        'accessible_institution_ids',
        isset($user) ? $user->permittedInstitutions->pluck('id')->all() : []
    ))->map(fn ($id) => (string) $id);
@endphp

<div class="grid gap-4 md:grid-cols-2">
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
        <select id="role" name="role" class="form-control @error('role') form-control-error @enderror">
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ $role }}</option>
            @endforeach
        </select>
        @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm font-medium text-slate-700" for="institution_id">Institucion principal</label>
        <select id="institution_id" name="institution_id" class="form-control @error('institution_id') form-control-error @enderror">
            <option value="">Sin institucion</option>
            @foreach ($institutions as $institution)
                <option value="{{ $institution->id }}" @selected((string) old('institution_id', $user->institution_id ?? '') === (string) $institution->id)>{{ $institution->nombre }}</option>
            @endforeach
        </select>
        @error('institution_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-medium text-slate-700" for="accessible_institution_ids">Instituciones adicionales habilitadas (opcional)</label>
        <p class="mt-1 text-xs text-slate-500">Estas instituciones se suman a la principal para permisos operativos.</p>
        <div id="accessible_institution_ids" class="app-subcard mt-2 grid gap-2 p-3 md:grid-cols-2">
            @foreach ($institutions as $institution)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="accessible_institution_ids[]"
                        value="{{ $institution->id }}"
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
