<?php

namespace App\Listeners;

use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        try {
            $event->user->notify(new WelcomeNotification);
        } catch (\Throwable $exception) {
            // A mail outage must not prevent an account from being created.
            report($exception);
        }
    }
}
