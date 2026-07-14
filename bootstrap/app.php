<?php

use App\Models\SecurityEvent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $middleware->appendToGroup('web', \App\Http\Middleware\LogSlowRequests::class);
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

        // Permission denials in this app arrive as two different exception
        // types by the time a render() callback sees them: Laravel's own
        // prepareException() converts Illuminate\Auth\Access\AuthorizationException
        // (thrown by $this->authorize() in every Livewire component) into
        // Symfony's AccessDeniedHttpException BEFORE any render callback
        // runs - so a callback typed to AuthorizationException itself would
        // never actually fire. abort_unless(..., 403) (used directly in a
        // few components, e.g. Teacher/Grades.php) throws a plain
        // HttpException with the same 403 status instead. Both are
        // HttpException subclasses, so hooking that common parent and
        // checking the status code catches both uniformly. Also one of the
        // exceptions shouldReport() filters out of report() above entirely,
        // which is the other reason this needs its own hook rather than
        // reusing the report() callback.
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }

            Log::warning('Permission denied: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'url' => $request->fullUrl(),
            ]);

            SecurityEvent::log('permission_denied', [
                'user_id' => Auth::id(),
                'context' => ['url' => $request->fullUrl(), 'message' => $e->getMessage()],
            ]);

            return null;
        });
    })->create();
