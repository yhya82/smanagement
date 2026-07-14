<?php

namespace Tests\Unit;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * AppServiceProvider::registerSlowQueryLogging() previously had nothing to
 * answer "which query is actually slow" - dispatching a QueryExecuted event
 * directly (rather than running a genuinely slow query, which SQLite in the
 * test suite can't produce on demand) exercises the real registered
 * listener without an artificial delay.
 */
class SlowQueryLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_slow_query_is_logged(): void
    {
        Log::shouldReceive('warning')->once()->with('Slow query', \Mockery::on(
            fn ($context) => $context['time_ms'] === 250.0
        ));

        Event::dispatch(new QueryExecuted('select * from users', [], 250.0, DB::connection()));
    }

    public function test_a_fast_query_is_not_logged(): void
    {
        Log::shouldReceive('warning')->never();

        Event::dispatch(new QueryExecuted('select * from users', [], 5.0, DB::connection()));
    }
}
