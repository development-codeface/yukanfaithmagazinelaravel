<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TrialEnrollment;
use App\Models\User;
use App\Services\TrialBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialPaymentSheetController extends Controller
{
    public function create(Request $request, int $planId, TrialBillingService $billing)
    {
        $request->validate(['accept_trial_billing' => 'required|accepted']);

        $user = $request->user();
        abort_unless($user, 401);

        $trial = Plan::findOrFail($planId);
        abort_unless($trial->isTrial() && (int) $trial->status === 1, 422, 'This trial is unavailable.');

        $annual = Plan::find($trial->trial_conversion_plan_id);
        abort_unless(
            $annual && !$annual->isTrial() && (int) $annual->status === 1 &&
            (float) $annual->digital_price > 0 &&
            (($annual->duration_type === 'years' && (int) $annual->duration === 1) ||
             ($annual->duration_type === 'months' && (int) $annual->duration === 12)),
            422,
            'The annual Digital package for this trial has not been configured.'
        );

        $enrollment = DB::transaction(function () use ($user, $trial, $annual) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            abort_if(
                $user->subscriptions()->whereHas('plan', fn ($q) => $q->where('is_trial', true))->exists(),
                422,
                'You have already used your free trial.'
            );

            $enrollment = TrialEnrollment::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 'pending',
                    'checkout_attempt' => 1,
                    'format_prices' => $annual->prices(),
                    'trial_plan_id' => $trial->id,
                    'annual_plan_id' => $annual->id,
                    'annual_amount' => $annual->digital_price,
                    'currency' => strtolower(config('services.stripe.currency', 'inr')),
                ]
            );

            abort_unless($enrollment->status === 'pending', 422, 'You have already used your free trial.');
            abort_unless(
                (int) $enrollment->trial_plan_id === (int) $trial->id &&
                (int) $enrollment->annual_plan_id === (int) $annual->id,
                422,
                'A different trial enrollment already exists for this account.'
            );
            abort_if($enrollment->stripe_subscription_id, 422, 'Your trial subscription has already been created.');

            return $enrollment;
        });

        // Reuse the Stripe Customer saved on users.stripe_customer_id.
        $customerId = $user->stripe_customer_id;

        if (!$customerId) {
            $customer = $billing->stripe('POST', 'customers', [
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => ['user_id' => (string) $user->id],
            ]);

            $customerId = $customer['id'] ?? null;
            abort_unless(
                is_string($customerId) && str_starts_with($customerId, 'cus_'),
                502,
                'Unable to create Stripe customer.'
            );

            $user->stripe_customer_id = $customerId;
            $user->save();
        }

        $metadata = [
            'trial_enrollment_id' => (string) $enrollment->id,
            'user_id' => (string) $user->id,
        ];

        $setupIntent = $billing->stripe('POST', 'setup_intents', [
            'customer' => $customerId,
            'usage' => 'off_session',
            'payment_method_types' => ['card'],
            'metadata' => $metadata,
        ]);

        abort_unless(
            !empty($setupIntent['id']) && !empty($setupIntent['client_secret']),
            502,
            'Unable to initialize card setup.'
        );

        $ephemeralKey = $billing->stripe('POST', 'ephemeral_keys', [
            'customer' => $customerId,
        ]);

        abort_unless(
            !empty($ephemeralKey['secret']),
            502,
            'Unable to initialize Stripe customer session.'
        );

        return response()->json([
            'publishable_key' => config('services.stripe.key'),
            'customer_id' => $customerId,
            'ephemeral_key' => $ephemeralKey['secret'],
            'setup_intent_client_secret' => $setupIntent['client_secret'],
            'setup_intent_id' => $setupIntent['id'],
            'trial_days' => $trial->trialDays(),
            'annual_amount' => $enrollment->annual_amount,
            'currency' => $enrollment->currency,
        ]);
    }

    public function confirm(Request $request, TrialBillingService $billing)
    {
        $data = $request->validate([
            'setup_intent_id' => ['required', 'string', 'regex:/^seti_[a-zA-Z0-9_]+$/'],
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $enrollment = TrialEnrollment::where('user_id', $user->id)->firstOrFail();

        if ($enrollment->stripe_subscription_id) {
            return response()->json([
                'trial' => $enrollment->fresh(),
                'message' => 'Your trial subscription has already been created.',
            ]);
        }

        abort_unless($enrollment->status === 'pending', 422, 'This trial enrollment cannot be confirmed.');

        $trial = Plan::findOrFail($enrollment->trial_plan_id);

        $setupIntent = $billing->stripe('GET', 'setup_intents/' . $data['setup_intent_id']);

        abort_unless(($setupIntent['status'] ?? '') === 'succeeded', 422, 'Card setup has not completed.');
        abort_unless(
            (int) ($setupIntent['metadata']['user_id'] ?? 0) === (int) $user->id &&
            (int) ($setupIntent['metadata']['trial_enrollment_id'] ?? 0) === (int) $enrollment->id,
            403,
            'This card setup does not belong to your account.'
        );
        abort_unless(
            ($setupIntent['customer'] ?? null) === $user->stripe_customer_id,
            422,
            'The Stripe customer does not match this account.'
        );

        $paymentMethodId = $setupIntent['payment_method'] ?? null;

        if (is_array($paymentMethodId)) {
            $paymentMethodId = $paymentMethodId['id'] ?? null;
        }

        abort_unless(
            is_string($paymentMethodId) && str_starts_with($paymentMethodId, 'pm_'),
            422,
            'Stripe did not return a valid saved payment method.'
        );

        $annual = Plan::findOrFail($enrollment->annual_plan_id);

        abort_unless(
            !$annual->isTrial() && (int) $annual->status === 1 && (float) $enrollment->annual_amount > 0,
            422,
            'The annual Digital package is no longer available.'
        );

        $billing->stripe('POST', 'customers/' . $user->stripe_customer_id, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);

        $metadata = [
            'trial_enrollment_id' => (string) $enrollment->id,
            'user_id' => (string) $user->id,
        ];

        // Subscription item price_data requires an existing Stripe Product ID.
        // This idempotency key prevents duplicate Products when the request is retried.
        $product = $billing->stripe('POST', 'products', [
            'name' => $annual->name . ' — Digital',
            'metadata' => $metadata,
        ], 'trial-payment-sheet-product-' . $enrollment->id);

        $productId = $product['id'] ?? null;

        abort_unless(
            is_string($productId) && str_starts_with($productId, 'prod_'),
            502,
            'Stripe did not create the annual subscription product.'
        );

        $stripeSubscription = $billing->stripe('POST', 'subscriptions', [
            'customer' => $user->stripe_customer_id,
            'items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($enrollment->currency),
                    'unit_amount' => (int) round((float) $enrollment->annual_amount * 100),
                    'recurring' => ['interval' => 'year'],
                    'product' => $productId,
                ],
            ]],
            'default_payment_method' => $paymentMethodId,
            'trial_period_days' => $trial->trialDays(),
            'trial_settings' => [
                'end_behavior' => ['missing_payment_method' => 'cancel'],
            ],
            'metadata' => $metadata,
        ], 'trial-payment-sheet-subscription-' . $enrollment->id);

        abort_unless(
            !empty($stripeSubscription['id']) &&
            ($stripeSubscription['metadata']['trial_enrollment_id'] ?? null) === (string) $enrollment->id,
            502,
            'Stripe did not create the expected trial subscription.'
        );

        $updatedEnrollment = $billing->sync($stripeSubscription);

        return response()->json([
            'trial' => $updatedEnrollment,
            'message' => 'Your '.$trial->trialDays().'-day trial has been created successfully.',
        ]);
    }
}
