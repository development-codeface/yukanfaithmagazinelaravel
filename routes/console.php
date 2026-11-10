<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('trials:sync', function () {
    if (!config('services.stripe.secret')) {
        $this->error('Stripe secret key is not configured.');
        return 1;
    }
    $failed = 0;
    \App\Models\TrialEnrollment::query()
        ->where(function ($query) {
            $query->whereNotNull('stripe_subscription_id')->orWhereNotNull('checkout_session_id');
        })
        ->eachById(function ($enrollment) use (&$failed) {
            try {
                app(\App\Services\TrialBillingService::class)->reconcile($enrollment);
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Unable to sync trial enrollment '.$enrollment->id.'. See application logs.');
                $failed++;
            }
        });
    $this->info('Trial billing sync finished. Failures: '.$failed);
    return $failed ? 1 : 0;
})->purpose('Verify trial Checkout and annual payments directly with Stripe');

Schedule::command('trials:sync')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
