<?php

use App\Services\ErrorTranslator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

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
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (config('app.debug')) {
                return null;
            }

            if ($exception instanceof ValidationException || $exception instanceof AuthenticationException) {
                return null;
            }

            /** @var ErrorTranslator $translator */
            $translator = app(ErrorTranslator::class);
            $friendly = $translator->translate($exception);
            $status = (int) ($friendly['status'] ?? 500);

            if ($request->expectsJson()) {
                return response()->json([
                    'title' => $friendly['title'],
                    'message' => $friendly['message'],
                    'reason' => $friendly['reason'],
                    'next_steps' => $friendly['next_steps'],
                ], $status);
            }

            $customErrorPages = [403, 404, 419, 429, 500, 503];

            if (! $request->isMethod('GET') && ! in_array($status, $customErrorPages, true)) {
                $redirect = back();

                if ($request->hasSession()) {
                    $redirect = $redirect->withInput($request->except([
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]));
                }

                return $redirect->with('error', $friendly['message']);
            }

            $view = view()->exists("errors.$status") ? "errors.$status" : 'errors.500';

            return response()->view($view, ['error' => $friendly], $status);
        });
    })
    ->create();
