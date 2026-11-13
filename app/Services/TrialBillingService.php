<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TrialEnrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TrialBillingService
{
    public function stripe(string $method, string $path, array $data = [], ?string $key = null): array
    {
        abort_unless(config('services.stripe.secret'), 503, 'Payment setup is unavailable.');
        $client = Http::asForm()->withBasicAuth(config('services.stripe.secret'), '')->timeout(25)
            ->withHeaders(['Stripe-Version' => '2024-06-20'] + ($key ? ['Idempotency-Key' => $key] : []));
        try {
            return ($method === 'GET' ? $client->get('https://api.stripe.com/v1/'.$path, $data)
                : $client->post('https://api.stripe.com/v1/'.$path, $data))->throw()->json();
       } catch (\Illuminate\Http\Client\RequestException $exception) {
    \Illuminate\Support\Facades\Log::error('Stripe API request failed', [
        'method' => $method,
        'path' => $path,
        'status' => $exception->response->status(),
        'response' => $exception->response->json(),
    ]);

    abort(502, 'Unable to contact the payment provider. Please try again.');

} catch (\Illuminate\Http\Client\ConnectionException $exception) {
    \Illuminate\Support\Facades\Log::error('Stripe connection failed', [
        'method' => $method,
        'path' => $path,
        'message' => $exception->getMessage(),
    ]);

    abort(502, 'Unable to contact the payment provider. Please try again.');
}
    }

    public function checkout(User $user, Plan $trial): array
    {
        abort_unless($trial->isTrial() && (int) $trial->status === 1, 422, 'This trial is unavailable.');
        $annual = Plan::find($trial->trial_conversion_plan_id);
        abort_unless($annual && !$annual->isTrial() && (int) $annual->status === 1 && (float) $annual->digital_price > 0
            && (($annual->duration_type === 'years' && (int) $annual->duration === 1) || ($annual->duration_type === 'months' && (int) $annual->duration === 12)), 422, 'The annual Digital package for this trial has not been configured.');

        $enrollment = DB::transaction(function () use ($user, $trial, $annual) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($user->subscriptions()->whereHas('plan', fn ($q) => $q->where('is_trial', true))->exists(), 422, 'You have already used your free trial.');
            $enrollment = TrialEnrollment::firstOrCreate(['user_id' => $user->id], [
                'status' => 'pending', 'checkout_attempt' => 1,
                'format_prices' => $annual->prices(),
                'trial_plan_id' => $trial->id, 'annual_plan_id' => $annual->id,
                'annual_amount' => $annual->digital_price, 'currency' => strtolower(config('services.stripe.currency', 'inr')),
            ]);
            abort_unless($enrollment->status === 'pending', 422, 'You have already used your free trial.');
            return $enrollment;
        });

        if ($enrollment->checkout_session_id) {
            $session = $this->stripe('GET', 'checkout/sessions/'.$enrollment->checkout_session_id);
            if (($session['status'] ?? '') === 'open') return $session;
            abort_if(($session['status'] ?? '') === 'complete', 422, 'Your trial checkout is already complete.');
            // Serialize retries so concurrent mobile/web requests share one Checkout.
            $enrollment = DB::transaction(function () use ($enrollment, $session) {
                $locked = TrialEnrollment::lockForUpdate()->findOrFail($enrollment->id);
                if ($locked->checkout_session_id === $session['id']) {
                    $locked->update(['checkout_session_id' => null, 'checkout_attempt' => $locked->checkout_attempt + 1]);
                }
                return $locked;
            });
        }
        $metadata = ['trial_enrollment_id' => (string) $enrollment->id, 'user_id' => (string) $user->id];
        $session = $this->stripe('POST', 'checkout/sessions', [
            'mode' => 'subscription', 'payment_method_types' => ['card'], 'payment_method_collection' => 'always',
            'customer_email' => $user->email, 'client_reference_id' => (string) $user->id,
            'success_url' => route('user.trial.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('user.subscriptions'),
            'line_items' => [['quantity' => 1, 'price_data' => [
                'currency' => $enrollment->currency, 'unit_amount' => (int) round((float) $enrollment->annual_amount * 100),
                'recurring' => ['interval' => 'year'],
                'product_data' => ['name' => $annual->name.' — Digital'],
            ]]],
            'metadata' => $metadata,
            'subscription_data' => ['trial_period_days' => $trial->trialDays(), 'metadata' => $metadata,
                'trial_settings' => ['end_behavior' => ['missing_payment_method' => 'cancel']]],
            'custom_text' => ['submit' => ['message' => $trial->trialDays().' days free, then '.$enrollment->currency.' '.$enrollment->annual_amount.' for one year of Digital access. Cancel before the trial ends to avoid this charge. No annual renewal.']],
        ], 'trial-checkout-'.$enrollment->id.'-'.$enrollment->checkout_attempt);
        $enrollment->update(['checkout_session_id' => $session['id']]);
        return $session;
    }

    public function confirm(array $session): TrialEnrollment
    {
        abort_unless(($session['mode'] ?? '') === 'subscription' && ($session['status'] ?? '') === 'complete', 422, 'Complete payment setup first.');
        $id = $session['metadata']['trial_enrollment_id'] ?? null;
        $enrollment = TrialEnrollment::findOrFail($id);
        abort_unless((int) ($session['metadata']['user_id'] ?? 0) === (int) $enrollment->user_id, 422);
        $stripeId = is_array($session['subscription'] ?? null) ? $session['subscription']['id'] : ($session['subscription'] ?? null);
        abort_unless(is_string($stripeId) && str_starts_with($stripeId, 'sub_'), 422);
        $stripe = $this->stripe('GET', 'subscriptions/'.$stripeId);
        abort_unless((int) ($stripe['metadata']['trial_enrollment_id'] ?? 0) === (int) $enrollment->id, 422);
        abort_if($stripe['status'] === 'trialing' && empty($stripe['default_payment_method']), 422, 'Payment method setup is not complete yet.');
        $this->sync($stripe);
        return $enrollment->fresh();
    }

    /** Recover abandoned browser returns and off-session billing directly from Stripe. */
    public function reconcile(TrialEnrollment $enrollment): TrialEnrollment
    {
        if (!$enrollment->stripe_subscription_id && $enrollment->checkout_session_id) {
            $session = $this->stripe('GET', 'checkout/sessions/'.$enrollment->checkout_session_id);
            abort_unless(($session['id'] ?? null) === $enrollment->checkout_session_id
                && (int) ($session['metadata']['trial_enrollment_id'] ?? 0) === (int) $enrollment->id
                && (int) ($session['metadata']['user_id'] ?? 0) === (int) $enrollment->user_id, 422);
            if (($session['status'] ?? '') === 'complete') {
                $enrollment = $this->confirm($session);
            }
        }
        if (!$enrollment->stripe_subscription_id) return $enrollment->fresh();

        $this->sync($this->stripe('GET', 'subscriptions/'.$enrollment->stripe_subscription_id));
        $params = ['subscription' => $enrollment->stripe_subscription_id, 'status' => 'paid', 'limit' => 100];
        do {
            $invoices = $this->stripe('GET', 'invoices', $params);
            foreach ($invoices['data'] as $invoice) {
                $invoiceSubscription = $invoice['subscription'] ?? $invoice['parent']['subscription_details']['subscription'] ?? null;
                if (is_array($invoiceSubscription)) $invoiceSubscription = $invoiceSubscription['id'];
                abort_unless($invoiceSubscription === $enrollment->stripe_subscription_id, 422);
                $this->paidInvoice($invoice);
            }
            $last = end($invoices['data']);
            if (!empty($invoices['has_more'])) {
                abort_unless($last && !empty($last['id']) && ($params['starting_after'] ?? null) !== $last['id'], 502);
                $params['starting_after'] = $last['id'];
            }
        } while (!empty($invoices['has_more']));

        return $enrollment->fresh();
    }

    public function sync(array $stripe): ?TrialEnrollment
    {
        $enrollment = TrialEnrollment::find($stripe['metadata']['trial_enrollment_id'] ?? 0);
        if (!$enrollment) return null;
        abort_unless((int) ($stripe['metadata']['user_id'] ?? 0) === (int) $enrollment->user_id, 422);
        $trialEnd = Carbon::createFromTimestampUTC($stripe['trial_end']);
        $annualEnd = $trialEnd->copy()->addYearNoOverflow();
        // Stripe Checkout cannot set cancel_at. Schedule the single-year end as
        // soon as Checkout completes; repeats are safe and respect user cancellation.
        if (in_array($stripe['status'], ['trialing', 'active', 'past_due'], true)
            && empty($stripe['cancel_at_period_end']) && !$enrollment->cancel_requested
            && (int) ($stripe['cancel_at'] ?? 0) !== $annualEnd->timestamp) {
            $stripe = $this->stripe('POST', 'subscriptions/'.$stripe['id'], ['cancel_at' => $annualEnd->timestamp], 'trial-single-year-'.$enrollment->id);
        }
        return DB::transaction(function () use ($enrollment, $stripe, $trialEnd, $annualEnd) {
            $enrollment = TrialEnrollment::lockForUpdate()->findOrFail($enrollment->id);
            abort_if($enrollment->stripe_subscription_id && $enrollment->stripe_subscription_id !== $stripe['id'], 422);
            $enrollment->update([
                'stripe_subscription_id' => $stripe['id'], 'status' => $stripe['status'],
                'trial_ends_at' => $trialEnd, 'ends_at' => $annualEnd,
                'cancel_requested' => $enrollment->cancel_requested || (bool) ($stripe['cancel_at_period_end'] ?? false) || $stripe['status'] === 'canceled',
            ]);
            if ($stripe['status'] === 'trialing' && !empty($stripe['default_payment_method'])) {
                Subscription::firstOrCreate(['transaction_id' => 'trial_'.$stripe['id']], [
                    'user_id' => $enrollment->user_id, 'plan_id' => $enrollment->trial_plan_id,
                    'start_date' => Carbon::createFromTimestampUTC($stripe['trial_start']), 'end_date' => $trialEnd,
                    'status' => 'active', 'payment_status' => 1, 'amount_paid' => 0,
                    'delivery_type' => 'digital', 'print_enabled' => false, 'currency' => $enrollment->currency,
                ]);
            }
            if (in_array($stripe['status'], ['canceled', 'unpaid', 'incomplete_expired'], true)) {
                // Keep receipts; only end access granted by this enrollment.
                Subscription::where('user_id', $enrollment->user_id)
                    ->where(fn ($q) => $q->where('transaction_id', 'trial_'.$stripe['id'])
                        ->orWhere('transaction_id', 'like', 'trialinvoice_'.$enrollment->id.'_%'))
                    ->update(['status' => 'inactive']);
            }
            return $enrollment;
        });
    }

    public function paidInvoice(array $invoice): void
    {
        $stripeId = $invoice['subscription'] ?? $invoice['parent']['subscription_details']['subscription'] ?? null;
        if (!$stripeId || ($invoice['status'] ?? '') !== 'paid' || (int) ($invoice['amount_paid'] ?? 0) <= 0) return;
        if (is_array($stripeId)) $stripeId = $stripeId['id'];
        $enrollment = $this->sync($this->stripe('GET', 'subscriptions/'.$stripeId));
        if (!$enrollment) return;
        abort_unless(strtolower($invoice['currency']) === $enrollment->currency
            && (int) $invoice['amount_paid'] === (int) round((float) $enrollment->annual_amount * 100), 422, 'Unexpected annual invoice amount.');
        $formatPrices = $enrollment->format_prices ?? Plan::find($enrollment->annual_plan_id)?->prices() ?? [];
        $formatPrices['digital'] = $enrollment->annual_amount;
        DB::transaction(function () use ($enrollment, $invoice, $formatPrices) {
            TrialEnrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            Subscription::firstOrCreate(['transaction_id' => 'trialinvoice_'.$enrollment->id.'_'.$invoice['id']], [
                'format_prices' => $formatPrices,
                'price_snapshot_source' => $enrollment->format_prices ? 'purchase' : 'legacy_current_prices',
                'user_id' => $enrollment->user_id, 'plan_id' => $enrollment->annual_plan_id,
                'start_date' => $enrollment->trial_ends_at, 'end_date' => $enrollment->ends_at,
                'status' => in_array($enrollment->status, ['canceled', 'unpaid'], true) ? 'inactive' : 'active',
                'payment_status' => 1, 'amount_paid' => (float) $invoice['amount_paid'] / 100,
                'delivery_type' => 'digital', 'print_enabled' => false, 'currency' => $enrollment->currency,
            ]);
        });
    }

    public function cancel(TrialEnrollment $enrollment): TrialEnrollment
    {
        abort_unless($enrollment->stripe_subscription_id, 422, 'No trial subscription to cancel.');
        // Fetch current status so a late trial cancellation cannot silently alter
        // a payment which Stripe has already started collecting.
        $stripe = $this->stripe('GET', 'subscriptions/'.$enrollment->stripe_subscription_id);
        if ($stripe['status'] !== 'canceled') {
            $stripe = $this->stripe('POST', 'subscriptions/'.$enrollment->stripe_subscription_id, ['cancel_at_period_end' => 'true']);
        }
        $enrollment->update(['cancel_requested' => true]);
        return $this->sync($stripe);
    }
}
