<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionUpgrade;
use App\Services\SubscriptionUpgradeService;
use Illuminate\Http\Request;

class SubscriptionUpgradeController extends Controller
{
    public function options(Request $request, SubscriptionUpgradeService $service)
    {
        return response()->json(['data' => $this->available($request, $service)]);
    }

    public function available(Request $request, SubscriptionUpgradeService $service): array
    {
        if (!$request->user()) return [];
        $options = [];
        $subscriptions = $request->user()->subscriptions()->with('plan')->where('status', 'active')->where('payment_status', 1)
            ->where('start_date', '<=', now())->where('end_date', '>', now())->latest('id')->get()->groupBy('plan_id');
        foreach ($subscriptions as $packageSubscriptions) {
            // A package already owned in both formats does not need another upgrade.
            if ($packageSubscriptions->contains('delivery_type', 'both')) continue;
            $subscription = $packageSubscriptions->first();
            try {
                $quote = $service->quote($subscription);
                $quote['upgrade_url'] = route('user.upgrades.show', $subscription);
                $quote['quote_endpoint'] = url('/api/user/subscriptions/'.$subscription->id.'/upgrade');
                $quote['payment_endpoint'] = url('/api/user/subscriptions/'.$subscription->id.'/upgrade/payment-intent');
                $options[] = $quote;
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                if ($exception->getStatusCode() !== 422) throw $exception;
            }
        }
        return $options;
    }

    public function show(Request $request, Subscription $subscription, SubscriptionUpgradeService $service)
    {
        $this->owner($request, $subscription);
        $quote = $service->quote($subscription);
        $pending = SubscriptionUpgrade::where('subscription_id', $subscription->id)->where('status', 'pending')->first();
        if ($pending) {
            $quote['amount'] = $pending->amount_minor;
            $quote['upgrade_amount'] = number_format($pending->amount_minor / 100, 2, '.', '');
        }
        return $request->is('api/*') ? response()->json(['quote' => $quote])
            : view('user.subscription-upgrade', compact('subscription', 'quote', 'pending'));
    }

    public function start(Request $request, Subscription $subscription, SubscriptionUpgradeService $service)
    {
        $this->owner($request, $subscription);
        $request->validate(['delivery_address' => 'nullable|array']);
        $address = $request->input('delivery_address') ?? $subscription->delivery_address ?? [];
        $native = $request->is('api/*');
        $upgrade = $service->prepare($subscription, $address, $native ? 'native' : 'hosted');
        if ($native) {
            $intent = $service->intent($upgrade);
            if (($intent['status'] ?? '') === 'succeeded') {
                $upgrade = $service->verify($upgrade->fresh());
                return response()->json(['requires_payment' => false, 'upgrade' => $upgrade,
                    'subscription' => $upgrade->subscription()->with('plan')->first()]);
            }
            return response()->json([
                'upgrade_id' => $upgrade->id, 'subscription_id' => $subscription->id, 'requires_payment' => true,
                'amount' => $upgrade->amount_minor, 'currency' => $upgrade->currency,
                'publishable_key' => config('services.stripe.key'), 'client_secret' => $intent['client_secret'],
                'payment_intent_id' => $intent['id'], 'ends_at' => $upgrade->subscription_end->toIso8601String(),
                'confirm_endpoint' => url('/api/user/subscription-upgrades/'.$upgrade->id.'/confirm'),
            ]);
        }
        $session = $service->checkout($upgrade);
        if (($session['status'] ?? '') === 'complete') {
            $service->verify($upgrade->fresh());
            return redirect()->route('user.subscriptions')->with('success', 'Your package is now Digital + Print. The expiry date is unchanged.');
        }
        return redirect()->away($session['url']);
    }

    public function confirm(Request $request, SubscriptionUpgrade $upgrade, SubscriptionUpgradeService $service)
    {
        abort_unless((int) $upgrade->user_id === (int) $request->user()->id, 403);
        if (!$request->is('api/*')) {
            $request->validate(['session_id' => 'required|string']);
            abort_unless($request->input('session_id') === $upgrade->checkout_session_id, 422, 'Checkout session does not match this upgrade.');
        }
        $upgrade = $service->verify($upgrade);
        return $request->is('api/*') ? response()->json([
            'message' => 'Package upgraded to Digital + Print. The expiry date is unchanged.',
            'upgrade' => $upgrade, 'subscription' => $upgrade->subscription()->with('plan')->first(),
        ]) : redirect()->route('user.subscriptions')->with('success', 'Your package is now Digital + Print. The expiry date is unchanged.');
    }

    private function owner(Request $request, Subscription $subscription): void
    {
        abort_unless((int) $subscription->user_id === (int) $request->user()->id, 403);
    }
}
