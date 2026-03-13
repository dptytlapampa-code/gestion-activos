<?php

namespace App\Http\Controllers;

use App\Services\ErrorTranslator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Throwable;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

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
