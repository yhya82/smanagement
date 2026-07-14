<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Same reasoning as AppServiceProvider::registerSlowQueryLogging() - a slow
 * *page* isn't always a slow *query* (Blade/Livewire render time, N+1s spread
 * across many small queries, etc. don't show up in a single-query threshold),
 * so this measures the whole request instead. 1000ms is deliberately looser
 * than the 200ms query threshold - a handful of individually-fast queries on
 * a big page is normal, this is for the outliers.
 */
class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $durationMs = (microtime(true) - $start) * 1000;

        if ($durationMs > 1000) {
            Log::warning('Slow request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => round($durationMs),
            ]);
        }

        return $response;
    }
}
