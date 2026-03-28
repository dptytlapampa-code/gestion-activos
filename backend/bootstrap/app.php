<?php

use App\Services\ErrorTranslator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            App\Http\Middleware\AssignAuditCorrelationId::class,
            App\Http\Middleware\EnsureActiveInstitution::class,
        ]);

        $middleware->alias([
            'auth' => Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'role' => App\Http\Middleware\EnsureUserRole::class,
        ]);

        $middleware->validateCsrfTokens(except: []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            /** @var ErrorTranslator $translator */
            $translator = app(ErrorTranslator::class);
            $friendly = $translator->translate($e);

            Log::warning('authorization denied', [
                'status' => 403,
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'active_institution_id' => app(\App\Services\ActiveInstitutionContext::class)->currentId($request->user()),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'title' => $friendly['title'] ?? 'No tiene permisos para esta accion',
                    'message' => $friendly['message'] ?? 'No tiene permisos para completar esta operacion.',
                    'reason' => $friendly['reason'] ?? 'La operacion fue rechazada por reglas de autorizacion.',
                    'next_steps' => $friendly['next_steps'] ?? 'Revise la institucion activa y sus permisos antes de intentar nuevamente.',
                ], 403);
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
                    $friendly['message'] ?? 'No tiene permisos para completar esta operacion.'
                );
            }

            return response()->view('errors.403', ['error' => $friendly], 403);
        });

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
