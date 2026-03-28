<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ActiveInstitutionContext
{
    public const SESSION_KEY = 'active_institution_id';

    private const REQUEST_ATTRIBUTE = '_active_institution_id';

    public function initializeForRequest(Request $request): ?int
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $activeInstitutionId = $this->currentId($user, $request->session());

        if ($activeInstitutionId === null) {
            $request->session()->forget(self::SESSION_KEY);
        } else {
            $request->session()->put(self::SESSION_KEY, $activeInstitutionId);
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $activeInstitutionId);

        return $activeInstitutionId;
    }

    public function currentId(?User $user, ?Session $session = null): ?int
    {
        if (! $user instanceof User) {
            return null;
        }

        $request = request();

        if ($request instanceof Request && $request->attributes->has(self::REQUEST_ATTRIBUTE)) {
            $requestInstitutionId = $this->normalizeId($request->attributes->get(self::REQUEST_ATTRIBUTE));

            if ($requestInstitutionId === null || $user->canAccessInstitution($requestInstitutionId)) {
                return $requestInstitutionId;
            }
        }

        $sessionInstitutionId = $this->normalizeId($session?->get(self::SESSION_KEY));

        if ($sessionInstitutionId !== null && $user->canAccessInstitution($sessionInstitutionId)) {
            return $sessionInstitutionId;
        }

        $fallbackSessionInstitutionId = $this->normalizeId($this->resolveSession()?->get(self::SESSION_KEY));

        if ($fallbackSessionInstitutionId !== null && $user->canAccessInstitution($fallbackSessionInstitutionId)) {
            return $fallbackSessionInstitutionId;
        }

        return $this->defaultId($user);
    }

    public function defaultId(User $user): ?int
    {
        $primaryInstitutionId = $this->normalizeId($user->institution_id);

        if ($primaryInstitutionId !== null && $user->canAccessInstitution($primaryInstitutionId)) {
            return $primaryInstitutionId;
        }

        return $user->accessibleInstitutionIds()->first();
    }

    public function set(User $user, int $institutionId, ?Session $session = null): ?Institution
    {
        $normalizedInstitutionId = $this->normalizeId($institutionId);

        if ($normalizedInstitutionId === null || ! $user->canAccessInstitution($normalizedInstitutionId)) {
            return null;
        }

        $institution = Institution::query()->find($normalizedInstitutionId);

        if ($institution === null) {
            return null;
        }

        ($session ?? $this->resolveSession())?->put(self::SESSION_KEY, $normalizedInstitutionId);

        $request = request();

        if ($request instanceof Request) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, $normalizedInstitutionId);
        }

        return $institution;
    }

    public function forget(?Session $session = null): void
    {
        ($session ?? $this->resolveSession())?->forget(self::SESSION_KEY);

        $request = request();

        if ($request instanceof Request) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, null);
        }
    }

    public function activeInstitution(?User $user, ?Session $session = null): ?Institution
    {
        if (! $user instanceof User) {
            return null;
        }

        $activeInstitutionId = $this->currentId($user, $session);

        if ($activeInstitutionId === null) {
            return null;
        }

        return $this->accessibleInstitutions($user)->firstWhere('id', $activeInstitutionId)
            ?? Institution::query()->find($activeInstitutionId);
    }

    /**
     * @return Collection<int, int>
     */
    public function accessibleInstitutionIds(?User $user): Collection
    {
        if (! $user instanceof User) {
            return collect();
        }

        return $user->accessibleInstitutionIds();
    }

    /**
     * @return Collection<int, Institution>
     */
    public function accessibleInstitutions(?User $user): Collection
    {
        if (! $user instanceof User) {
            return collect();
        }

        return Institution::query()
            ->when(
                ! $user->hasRole(User::ROLE_SUPERADMIN),
                fn ($query) => $query->whereIn('id', $user->accessibleInstitutionIds()->all())
            )
            ->orderByRaw(
                "case when scope_type = ? then 0 else 1 end",
                [Institution::SCOPE_GLOBAL]
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'scope_type']);
    }

    public function isActiveInstitution(?User $user, ?int $institutionId, ?Session $session = null): bool
    {
        $normalizedInstitutionId = $this->normalizeId($institutionId);

        return $normalizedInstitutionId !== null
            && $this->currentId($user, $session) === $normalizedInstitutionId;
    }

    public function bypassesActiveInstitutionForGlobalModules(?User $user): bool
    {
        return $this->operatesWithGlobalScope($user);
    }

    public function operatesWithGlobalScope(?User $user, ?Session $session = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole(User::ROLE_SUPERADMIN)) {
            return true;
        }

        return $this->activeInstitution($user, $session)?->isGlobalScope() ?? false;
    }

    public function isWithinGlobalAdministrationScope(?User $user, ?int $institutionId, ?Session $session = null): bool
    {
        $normalizedInstitutionId = $this->normalizeId($institutionId);

        if ($normalizedInstitutionId === null || ! $user instanceof User) {
            return false;
        }

        if ($this->operatesWithGlobalScope($user, $session)) {
            return true;
        }

        return $this->isActiveInstitution($user, $normalizedInstitutionId, $session);
    }

    /**
     * @return array<int, int>
     */
    public function activeScopeIds(?User $user, ?Session $session = null): array
    {
        $activeInstitutionId = $this->currentId($user, $session);

        return $activeInstitutionId !== null ? [$activeInstitutionId] : [];
    }

    /**
     * @return array<int, int>|null
     */
    public function globalAdministrationScopeIds(?User $user, ?Session $session = null): ?array
    {
        if (! $user instanceof User) {
            return [];
        }

        if ($this->operatesWithGlobalScope($user, $session)) {
            return null;
        }

        return $this->activeScopeIds($user, $session);
    }

    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $institutionId = (int) $value;

        return $institutionId > 0 ? $institutionId : null;
    }

    private function resolveSession(): ?Session
    {
        return app()->bound('session.store')
            ? app('session.store')
            : null;
    }
}
