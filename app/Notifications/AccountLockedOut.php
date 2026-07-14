<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class AccountLockedOut extends Notification
{
    public function __construct(
        private readonly string $email,
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'account_locked_out',
            'title' => 'Account locked out after repeated failed logins',
            'message' => "The account for {$this->email} was temporarily locked out after too many failed login attempts.",
            'data' => ['email' => $this->email],
        ];
    }
}
