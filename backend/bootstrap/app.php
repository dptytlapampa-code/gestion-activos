<?php

use App\Services\ErrorTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'role' => App\Http\Middleware\EnsureUserRole::class,
        ]);

        $middleware->validateCsrfTokens(except: []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (QueryException $e, Request $request) {
            if (config('app.debug')) {
                return null;
            }

            /** @var ErrorTranslator $translator */
            $translator = app(ErrorTranslator::class);

            if (method_exists($translator, 'render')) {
                return $translator->render($e, $request);
            }

            $friendly = $translator->translate($e);
            $status = (int) ($friendly['status'] ?? 500);

            if ($request->expectsJson()) {
                return response()->json([
                    'title' => $friendly['title'] ?? 'No se pudo completar la operacion',
                    'message' => $friendly['message'] ?? 'Ocurrio un error al procesar los datos.',
                    'reason' => $friendly['reason'] ?? 'Se detecto un problema con la operacion solicitada.',
                    'next_steps' => $friendly['next_steps'] ?? 'Revise los datos y vuelva a intentar.',
                ], $status);
            }

            if (! $request->isMethod('GET')) {
                $redirect = back();

                if ($request->hasSession()) {
                    $redirect = $redirect->withInput($request->except([
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]));
                }

                return $redirect->with(
                    'error',
                    $friendly['message'] ?? 'No se pudo completar la operacion con los datos enviados.'
                );
            }

            $view = view()->exists("errors.$status") ? "errors.$status" : 'errors.500';

            return response()->view($view, ['error' => $friendly], $status);
        });
    })
    ->create();