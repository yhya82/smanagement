<?php

namespace Tests\Unit;

use App\Http\Middleware\LogSlowRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogSlowRequestsTest extends TestCase
{
    public function test_a_slow_request_is_logged(): void
    {
        Log::shouldReceive('warning')->once()->with('Slow request', \Mockery::on(
            fn ($context) => $context['duration_ms'] >= 1000
        ));

        (new LogSlowRequests)->handle(Request::create('/slow'), function () {
            usleep(1_100_000);

            return new Response();
        });
    }

    public function test_a_fast_request_is_not_logged(): void
    {
        Log::shouldReceive('warning')->never();

        (new LogSlowRequests)->handle(Request::create('/fast'), fn () => new Response());
    }
}
