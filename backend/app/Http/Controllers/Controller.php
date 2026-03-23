<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use App\Services\ActiveInstitutionContext;
use App\Services\ErrorTranslator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Throwable;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function institutionContext(): ActiveInstitutionContext
    {
        return app(ActiveInstitutionContext::class);
    }

    protected function activeInstitutionId(?User $user = null): ?int
    {
        return $this->institutionContext()->currentId($user ?? request()->user());
    }

    protected function activeInstitution(?User $user = null): ?Institution
    {
        return $this->institutionContext()->activeInstitution($user ?? request()->user());
    }

    /**
     * @return Collection<int, Institution>
     */
    protected function accessibleInstitutions(?User $user = null): Collection
    {
        return $this->institutionContext()->accessibleInstitutions($user ?? request()->user());
    }

    public function isActiveInstitution(?User $user, ?int $institutionId): bool
    {
        return $this->institutionContext()->isActiveInstitution($user, $institutionId);
    }

    protected function friendlyErrorRedirect(Throwable $exception, bool $withInput = true): RedirectResponse
    {
        if (config('app.debug')) {
            throw $exception;
        }

        /** @var ErrorTranslator $translator */
        $translator = app(ErrorTranslator::class);
        $friendly = $translator->translate($exception);

        $redirect = back();

        if ($withInput) {
            $request = request();

            $redirect = $redirect->withInput($request->except([
                'password',
                'password_confirmation',
                'current_password',
            ]));
        }

        return $redirect->with('error', $friendly['message']);
    }
}
