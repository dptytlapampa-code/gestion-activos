@csrf
@php
    $selectedAdditionalInstitutionIds = collect(old(
        'accessible_institution_ids',
        isset($user) ? $user->permittedInstitutions->pluck('id')->all() : []
    ))->map(fn ($id) => (string) $id);
@endphp
<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="text-sm" for="name">Nombre</label>
        <input id="name" name="name" value="{{ old('name', $user->name ?? '') }}" class="mt-1 w-full rounded border px-3 py-2 @error('name') border-red-300 @else border-slate-300 @enderror" />
        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm" for="email">Email</label>
        <input id="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="mt-1 w-full rounded border px-3 py-2 @error('email') border-red-300 @else border-slate-300 @enderror" />
        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    @if(!isset($user))
        <div>
            <label class="text-sm" for="password">Password</label>
            <input id="password" type="password" name="password" class="mt-1 w-full rounded border px-3 py-2 @error('password') border-red-300 @else border-slate-300 @enderror" />
            @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        <label class="text-sm" for="role">Rol</label>
        <select id="role" name="role" class="mt-1 w-full rounded border px-3 py-2 @error('role') border-red-300 @else border-slate-300 @enderror">
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ $role }}</option>
            @endforeach
        </select>
        @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="text-sm" for="institution_id">Institucion principal</label>
        <select id="institution_id" name="institution_id" class="mt-1 w-full rounded border px-3 py-2 @error('institution_id') border-red-300 @else border-slate-300 @enderror">
            <option value="">Sin institucion</option>
            @foreach($institutions as $institution)
                <option value="{{ $institution->id }}" @selected((string) old('institution_id', $user->institution_id ?? '') === (string) $institution->id)>{{ $institution->nombre }}</option>
            @endforeach
        </select>
        @error('institution_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="text-sm" for="accessible_institution_ids">Instituciones adicionales habilitadas (opcional)</label>
        <p class="mt-1 text-xs text-slate-500">Estas instituciones se suman a la principal para permisos operativos.</p>
        <div id="accessible_institution_ids" class="mt-2 grid gap-2 rounded border border-slate-200 p-3 md:grid-cols-2">
            @foreach($institutions as $institution)
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
<button class="mt-4 rounded bg-indigo-600 px-4 py-2 text-white">Guardar</button>
