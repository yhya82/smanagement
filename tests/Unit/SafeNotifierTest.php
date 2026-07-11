<?php

namespace Tests\Unit;

use App\Support\SafeNotifier;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class SafeNotifierTest extends TestCase
{
    public function test_a_failing_notification_is_swallowed_not_rethrown(): void
    {
        $notifiable = new class
        {
            public int $id = 1;

            public function notify($notification): void
            {
                throw new RuntimeException('channel unavailable');
            }
        };

        Log::shouldReceive('error')->once();

        // The whole point of SafeNotifier: this must not throw, even though
        // the underlying notify() call does.
        SafeNotifier::send($notifiable, $this->createStub(Notification::class));

        $this->addToAssertionCount(1);
    }

    public function test_a_successful_notification_is_delivered_normally(): void
    {
        $notifiable = new class
        {
            public int $id = 1;

            public bool $notified = false;

            public function notify($notification): void
            {
                $this->notified = true;
            }
        };

        SafeNotifier::send($notifiable, $this->createStub(Notification::class));

        $this->assertTrue($notifiable->notified);
    }
}
