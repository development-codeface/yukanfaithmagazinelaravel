<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionUpgrade;
use App\Support\PrintDelivery;
use Illuminate\Support\Facades\DB;

class SubscriptionUpgradeService
{
    public function quote(Subscription $subscription): array
    {
        $subscription->loadMissing('plan');
        abort_unless($subscription->plan && !$subscription->plan->isTrial(), 422, 'Only paid packages can be upgraded.');
        abort_unless($subscription->status === 'active' && $subscription->payment_status
            && $subscription->start_date && $subscription->start_date->lte(now())
            && $subscription->end_date && $subscription->end_date->gt(now()), 422, 'Only a currently active subscription can be upgraded.');
        abort_unless(in_array($subscription->delivery_type, ['digital', 'print'], true), 422, 'This package already includes Digital + Print.');
        abort_unless((float) $subscription->amount_paid - (float) $subscription->support_amount > 0, 422, 'A paid package is required.');
        $prices = $subscription->format_prices;
        abort_unless(is_array($prices) && isset($prices[$subscription->delivery_type], $prices['both'])
            && is_numeric($prices[$subscription->delivery_type]) && is_numeric($prices['both']), 422, 'Combined format pricing is not available for this subscription.');
        $source = (int) round((float) $prices[$subscription->delivery_type] * 100);
        $target = (int) round((float) $prices['both'] * 100);
        abort_unless($target > $source, 422, 'The combined package must have a higher price to upgrade.');
        $total = $subscription->end_date->timestamp - $subscription->start_date->timestamp;
        $remaining = max(0, $subscription->end_date->timestamp - now()->timestamp);
        $amount = (int) round(($target - $source) * $remaining / $total);
        $pending = SubscriptionUpgrade::where('subscription_id', $subscription->id)->where('status', 'pending')->first();
        if ($pending) {
            $amount = $pending->amount_minor;
            $remaining = max(0, $subscription->end_date->timestamp - $pending->quoted_at->timestamp);
        }
        // Avoid rounding a paid upgrade down to an unverified free entitlement.
        abort_unless($amount > 0, 422, 'Too little time remains for a paid upgrade.');
        return [
            'subscription_id' => $subscription->id, 'plan_id' => $subscription->plan_id, 'plan_name' => $subscription->plan->name,
            'from_type' => $subscription->delivery_type, 'to_type' => 'both',
            'current_format_price' => number_format($source / 100, 2, '.', ''),
            'combined_format_price' => number_format($target / 100, 2, '.', ''),
            'amount' => $amount, 'upgrade_amount' => number_format($amount / 100, 2, '.', ''),
            'currency' => strtoupper($pending?->currency ?: config('services.stripe.currency', 'usd')),
            'remaining_days' => round($remaining / 86400, 2), 'total_days' => round($total / 86400, 2),
            'ends_at' => $subscription->end_date->toIso8601String(), 'support_amount' => '0.00',
            'price_snapshot_source' => $subscription->price_snapshot_source,
        ];
    }

    public function prepare(Subscription $subscription, array $address, string $flow): SubscriptionUpgrade
    {
        $this->quote($subscription);
        $address = PrintDelivery::address($address);
        app(TrialBillingService::class)->cancelTrialForPurchase($subscription->user);
        return DB::transaction(function () use ($subscription, $address, $flow) {
            $subscription = Subscription::lockForUpdate()->findOrFail($subscription->id);
            $existing = SubscriptionUpgrade::where('subscription_id', $subscription->id)->first();
            if ($existing) {
                abort_unless($existing->status === 'pending', 422, 'This subscription has already been upgraded.');
                abort_unless($existing->payment_flow === $flow, 422, 'Continue the upgrade using the original payment channel.');
                abort_unless($existing->subscription_end->gt(now()), 422, 'This subscription has expired.');
                abort_unless($subscription->status === 'active' && $subscription->payment_status
                    && $subscription->delivery_type === $existing->from_type
                    && (int) $subscription->plan_id === (int) $existing->plan_id
                    && $subscription->start_date->equalTo($existing->subscription_start)
                    && $subscription->end_date->equalTo($existing->subscription_end), 409, 'Subscription changed after the upgrade quote was created.');
                // Keep address and price immutable once payment starts. Retrying
                // the same upgrade must not create another payment or receipt.
                return $existing;
            }
            $quote = $this->quote($subscription);
            return SubscriptionUpgrade::create([
                'subscription_id' => $subscription->id, 'user_id' => $subscription->user_id, 'plan_id' => $subscription->plan_id,
                'from_type' => $subscription->delivery_type, 'to_type' => 'both', 'amount_minor' => $quote['amount'],
                'currency' => strtolower($quote['currency']), 'format_prices' => $subscription->format_prices,
                'delivery_address' => PrintDelivery::address($address), 'quoted_at' => now(),
                'subscription_start' => $subscription->start_date, 'subscription_end' => $subscription->end_date,
                'payment_flow' => $flow, 'status' => 'pending', 'checkout_attempt' => 1,
            ]);
        });
    }

    public function metadata(SubscriptionUpgrade $upgrade): array
    {
        return ['purchase_type' => 'subscription_upgrade', 'upgrade_id' => (string) $upgrade->id,
            'user_id' => (string) $upgrade->user_id, 'subscription_id' => (string) $upgrade->subscription_id];
    }

    public function intent(SubscriptionUpgrade $upgrade): array
    {
        $stripe = app(TrialBillingService::class);
        if ($upgrade->payment_intent_id) return $stripe->stripe('GET', 'payment_intents/'.$upgrade->payment_intent_id);
        $intent = $stripe->stripe('POST', 'payment_intents', [
            'amount' => $upgrade->amount_minor, 'currency' => $upgrade->currency,
            'automatic_payment_methods' => ['enabled' => 'true'],
            'description' => 'Subscription upgrade to Digital + Print', 'metadata' => $this->metadata($upgrade),
        ], 'subscription-upgrade-intent-'.$upgrade->id);
        $upgrade->update(['payment_intent_id' => $intent['id']]);
        return $intent;
    }

    public function checkout(SubscriptionUpgrade $upgrade): array
    {
        $stripe = app(TrialBillingService::class);
        if ($upgrade->checkout_session_id) {
            $session = $stripe->stripe('GET', 'checkout/sessions/'.$upgrade->checkout_session_id);
            if (($session['status'] ?? '') !== 'expired') return $session;
            $upgrade = DB::transaction(function () use ($upgrade, $session) {
                $locked = SubscriptionUpgrade::lockForUpdate()->findOrFail($upgrade->id);
                if ($locked->checkout_session_id === $session['id']) $locked->update([
                    'checkout_session_id' => null, 'checkout_attempt' => $locked->checkout_attempt + 1,
                ]);
                return $locked;
            });
        }
        $session = $stripe->stripe('POST', 'checkout/sessions', [
            'mode' => 'payment', 'payment_method_types' => ['card'],
            'success_url' => route('user.upgrades.success', $upgrade).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('user.upgrades.show', $upgrade->subscription_id),
            'metadata' => $this->metadata($upgrade),
            'payment_intent_data' => ['metadata' => $this->metadata($upgrade)],
            'line_items' => [['quantity' => 1, 'price_data' => ['unit_amount' => $upgrade->amount_minor,
                'currency' => $upgrade->currency, 'product_data' => ['name' => 'Upgrade to Digital + Print']]]],
        ], 'subscription-upgrade-checkout-'.$upgrade->id.'-'.$upgrade->checkout_attempt);
        $upgrade->update(['checkout_session_id' => $session['id']]);
        return $session;
    }

    public function verify(SubscriptionUpgrade $upgrade): SubscriptionUpgrade
    {
        if ($upgrade->status === 'paid') return $upgrade;
        $stripe = app(TrialBillingService::class);
        if ($upgrade->payment_flow === 'hosted') {
            abort_unless($upgrade->checkout_session_id, 422, 'Payment has not started.');
            $session = $stripe->stripe('GET', 'checkout/sessions/'.$upgrade->checkout_session_id);
            abort_unless(($session['mode'] ?? '') === 'payment' && ($session['status'] ?? '') === 'complete'
                && ($session['payment_status'] ?? '') === 'paid', 422, 'Payment is not complete.');
            $this->checkMetadata($upgrade, $session['metadata'] ?? []);
            $intentId = is_array($session['payment_intent'] ?? null) ? $session['payment_intent']['id'] : ($session['payment_intent'] ?? null);
            abort_unless(is_string($intentId) && str_starts_with($intentId, 'pi_'), 422);
            $intent = $stripe->stripe('GET', 'payment_intents/'.$intentId);
        } else {
            abort_unless($upgrade->payment_intent_id, 422, 'Payment has not started.');
            $intent = $stripe->stripe('GET', 'payment_intents/'.$upgrade->payment_intent_id);
        }
        $this->checkMetadata($upgrade, $intent['metadata'] ?? []);
        abort_unless(($intent['status'] ?? '') === 'succeeded', 422, 'Payment is not complete.');
        abort_unless((int) ($intent['amount_received'] ?? -1) === $upgrade->amount_minor
            && strtolower($intent['currency'] ?? '') === $upgrade->currency, 422, 'Upgrade payment is incomplete or does not match the quote.');
        return $this->apply($upgrade, $intent['id']);
    }

    private function checkMetadata(SubscriptionUpgrade $upgrade, array $metadata): void
    {
        abort_unless(($metadata['purchase_type'] ?? '') === 'subscription_upgrade'
            && (int) ($metadata['upgrade_id'] ?? 0) === $upgrade->id
            && (int) ($metadata['user_id'] ?? 0) === (int) $upgrade->user_id
            && (int) ($metadata['subscription_id'] ?? 0) === (int) $upgrade->subscription_id, 422, 'Payment does not match this upgrade.');
    }

    private function apply(SubscriptionUpgrade $upgrade, string $intentId): SubscriptionUpgrade
    {
        return DB::transaction(function () use ($upgrade, $intentId) {
            // Lock in the same order as prepare() to avoid concurrent duplicates.
            $subscription = Subscription::lockForUpdate()->findOrFail($upgrade->subscription_id);
            $upgrade = SubscriptionUpgrade::lockForUpdate()->findOrFail($upgrade->id);
            if ($upgrade->status === 'paid') return $upgrade;
            abort_unless((int) $subscription->user_id === (int) $upgrade->user_id
                && $subscription->delivery_type === $upgrade->from_type && (int) $subscription->plan_id === (int) $upgrade->plan_id
                && $subscription->start_date->equalTo($upgrade->subscription_start) && $subscription->end_date->equalTo($upgrade->subscription_end),
                409, 'Subscription changed during checkout. Please contact support about this payment.');
            $reviewedAt = $upgrade->from_type === 'print' && $subscription->delivery_address === $upgrade->delivery_address
                ? $subscription->print_acknowledged_at : null;
            $subscription->update(['delivery_type' => 'both', 'print_enabled' => true,
                'delivery_address' => $upgrade->delivery_address, 'print_acknowledged_at' => $reviewedAt]);
            // Original amount, transaction, support and dates remain unchanged.
            $upgrade->update(['status' => 'paid', 'payment_intent_id' => $intentId, 'paid_at' => now(), 'applied_at' => now()]);
            return $upgrade;
        });
    }

    public function reconcile(SubscriptionUpgrade $upgrade): void
    {
        if (!$upgrade->subscription_end->gt(now()) && !$upgrade->checkout_session_id && !$upgrade->payment_intent_id) return;
        if ($upgrade->payment_flow === 'hosted' && !$upgrade->checkout_session_id) $this->checkout($upgrade);
        if ($upgrade->payment_flow === 'native' && !$upgrade->payment_intent_id) $this->intent($upgrade);
        try { $this->verify($upgrade->fresh()); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Waiting/failed payments are not completion; provider/consistency
            // errors must remain visible to the scheduled command.
            if ($e->getStatusCode() !== 422 || $e->getMessage() !== 'Payment is not complete.') throw $e;
        }
    }
}
