<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\TrialEnrollment;
use App\Services\TrialBillingService;
use Illuminate\Http\Request;

class TrialBillingController extends Controller
{
    public function start(Request $request, Plan $plan, TrialBillingService $billing)
    {
        $request->validate(['accept_trial_billing' => 'required|accepted']);
        $session = $billing->checkout($request->user(), $plan);
        return $request->is('api/*') ? response()->json([
            'requires_payment_setup' => true, 'checkout_url' => $session['url'],
            'session_id' => $session['id'], 'trial_days' => 7,
            'message' => 'Open checkout_url to set up payment details. Confirm the session after Checkout completes.',
        ]) : redirect()->away($session['url']);
    }

    public function confirm(Request $request, TrialBillingService $billing)
    {
        $data = $request->validate(['session_id' => 'required|string|regex:/^cs_[a-zA-Z0-9_]+$/']);
        $session = $billing->stripe('GET', 'checkout/sessions/'.$data['session_id']);
        abort_unless((int) ($session['metadata']['user_id'] ?? 0) === (int) $request->user()->id, 403);
        $enrollment = $billing->confirm($session);
        return $request->is('api/*') ? response()->json(['trial' => $enrollment])
            : redirect()->route('user.subscriptions')->with('success', 'Payment setup complete. Your trial status and cancellation option are shown below.');
    }

    public function status(Request $request)
    {
        return response()->json(['trial' => TrialEnrollment::where('user_id', $request->user()->id)->first()]);
    }

    public function cancel(Request $request, TrialBillingService $billing)
    {
        $enrollment = TrialEnrollment::where('user_id', $request->user()->id)->firstOrFail();
        $enrollment = $billing->cancel($enrollment);
        return $request->is('api/*') ? response()->json(['trial' => $enrollment, 'message' => 'Cancellation recorded. Access remains until the current period ends.'])
            : redirect()->route('user.subscriptions')->with('success', 'Cancellation recorded. You keep access until the current period ends.');
    }

    public function webhook(Request $request, TrialBillingService $billing)
    {
        $secret = config('services.stripe.subscription_webhook_secret');
        abort_unless($secret, 503);
        $parts = [];
        foreach (explode(',', $request->header('Stripe-Signature', '')) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) === 2) $parts[$pair[0]][] = $pair[1];
        }
        $timestamp = $parts['t'][0] ?? '';
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300, 400);
        $signature = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(collect($parts['v1'] ?? [])->contains(fn ($value) => hash_equals($signature, $value)), 400);
        $event = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $object = $event['data']['object'] ?? [];
        $type = $event['type'] ?? '';
        if ($type === 'checkout.session.completed' && isset($object['metadata']['trial_enrollment_id'])) {
            $billing->confirm($billing->stripe('GET', 'checkout/sessions/'.$object['id']));
        } elseif (in_array($type, ['customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted'], true)
            && isset($object['metadata']['trial_enrollment_id'])) {
            $billing->sync($billing->stripe('GET', 'subscriptions/'.$object['id']));
        } elseif ($type === 'invoice.paid') {
            $billing->paidInvoice($billing->stripe('GET', 'invoices/'.$object['id']));
        } elseif ($type === 'invoice.payment_failed') {
            $id = $object['subscription'] ?? $object['parent']['subscription_details']['subscription'] ?? null;
            if (is_array($id)) $id = $id['id'];
            if ($id) $billing->sync($billing->stripe('GET', 'subscriptions/'.$id));
        }
        return response()->json(['received' => true]);
    }
}
