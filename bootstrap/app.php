<?php

use App\Http\Middleware\AuthGates;
use App\Http\Middleware\DemoMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {

        /*
        |--------------------------------------------------------------------------
        | Global Middleware
        |--------------------------------------------------------------------------
        */

        $middleware->use([
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Web Middleware Group
        |--------------------------------------------------------------------------
        */

        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | API Middleware Group
        |--------------------------------------------------------------------------
        | Removed ':api' limiter to avoid
        | "Rate limiter [api] is not defined"
        |--------------------------------------------------------------------------
        */

        $middleware->group('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Route Middleware Aliases
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'auth.gates' => AuthGates::class,
            'api.token'  => \App\Http\Middleware\ApiTokenAuth::class,
            'auth'       => \App\Http\Middleware\Authenticate::class,
            'guest'      => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'verified'   => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })

    ->create();
