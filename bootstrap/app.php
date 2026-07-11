<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureUserHasChangedPassword::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel already logs the exception itself with its full trace -
        // this adds the one thing that trace doesn't have: who was making
        // the request when it broke, since "which user hit this" is
        // usually the first question in tracking down a production report.
        $exceptions->report(function (Throwable $e) {
            Log::error('Unhandled exception: '.$e->getMessage(), [
                'exception' => get_class($e),
                'user_id' => Auth::id(),
                'url' => request()?->fullUrl(),
            ]);
        });
    })->create();
