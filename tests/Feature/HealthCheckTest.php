<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * /up previously only proved the app booted - it said nothing about
 * whether a queue worker was actually alive, which is exactly the failure
 * mode the README warns nothing else detects. AppServiceProvider::
 * registerHealthChecks() hooks Illuminate\Foundation\Events\DiagnosingHealth
 * to make /up fail loudly instead.
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_reports_healthy_under_normal_conditions(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_up_reports_unhealthy_when_the_queue_backlog_is_too_large(): void
    {
        $rows = array_fill(0, 501, [
            'queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'created_at' => time(), 'available_at' => time(),
        ]);
        DB::table('jobs')->insert($rows);

        $this->get('/up')->assertServerError();
    }

    public function test_up_reports_unhealthy_when_too_many_jobs_have_failed(): void
    {
        $rows = array_map(fn () => [
            'uuid' => (string) \Illuminate\Support\Str::uuid(), 'connection' => 'database', 'queue' => 'default',
            'payload' => '{}', 'exception' => 'x', 'failed_at' => now(),
        ], range(1, 101));
        DB::table('failed_jobs')->insert($rows);

        $this->get('/up')->assertServerError();
    }
}
