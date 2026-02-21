@csrf
<div class="grid gap-4 md:grid-cols-2">
    <div><label class="text-sm">Nombre</label><input name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full rounded border px-3 py-2">@error('name')<p class="text-rose-600 text-xs">{{ $message }}</p>@enderror</div>
    <div><label class="text-sm">Email</label><input name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded border px-3 py-2">@error('email')<p class="text-rose-600 text-xs">{{ $message }}</p>@enderror</div>
    @if(!isset($user))
    <div><label class="text-sm">Password</label><input type="password" name="password" class="w-full rounded border px-3 py-2">@error('password')<p class="text-rose-600 text-xs">{{ $message }}</p>@enderror</div>
    @endif
    <div><label class="text-sm">Rol</label><select name="role" class="w-full rounded border px-3 py-2">@foreach($roles as $role)<option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ $role }}</option>@endforeach</select>@error('role')<p class="text-rose-600 text-xs">{{ $message }}</p>@enderror</div>
    <div><label class="text-sm">Institución</label><select name="institution_id" class="w-full rounded border px-3 py-2"><option value="">Sin institución</option>@foreach($institutions as $institution)<option value="{{ $institution->id }}" @selected((string) old('institution_id', $user->institution_id ?? '') === (string) $institution->id)>{{ $institution->nombre }}</option>@endforeach</select>@error('institution_id')<p class="text-rose-600 text-xs">{{ $message }}</p>@enderror</div>
</div>
<button class="mt-4 rounded bg-indigo-600 px-4 py-2 text-white">Guardar</button>
