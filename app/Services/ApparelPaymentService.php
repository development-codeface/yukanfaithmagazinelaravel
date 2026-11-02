<?php

namespace App\Services;

use App\Models\ApparelOrder;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApparelPaymentService
{
    public function cancel(ApparelOrder $order): ApparelOrder
    {
        return DB::transaction(function () use ($order) {
            $order = ApparelOrder::lockForUpdate()->findOrFail($order->id);
            abort_unless($order->api_checkout, 422, 'Only online checkouts can be cancelled here.');
            if ($order->status === 'cancelled') return $order;
            abort_unless($order->status === 'pending' && $order->payment_status === 'pending' && ! $order->payment_intent_id && $order->payment_flow !== 'hosted', 422, 'An order with a payment attempt requires staff assistance to cancel.');
            foreach ($order->items()->orderBy('product_id')->get() as $item) {
                $product = \App\Models\ApparelProduct::withTrashed()->lockForUpdate()->find($item->product_id);
                if ($item->variant_id) {
                    \App\Models\ApparelProductVariant::where('apparel_product_id', $item->product_id)->whereKey($item->variant_id)->increment('stock', $item->quantity);
                } elseif ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }
            $order->update(['status' => 'cancelled']);
            return $order;
        });
    }

    public function stripe(string $method, string $path, array $form = [], array $headers = []): array
    {
        abort_unless(config('services.stripe.secret'), 503, 'Stripe is not configured.');
        $client = app(Client::class, ['config' => ['base_uri' => 'https://api.stripe.com/v1/', 'timeout' => 20]]);
        $options = ['auth' => [config('services.stripe.secret'), ''], 'headers' => $headers];
        if ($form) {
            $options['form_params'] = $form;
        }

        return json_decode((string) $client->request($method, $path, $options)->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function sheet(ApparelOrder $order, $user): array
    {
        // Serialize attempts so concurrent requests reuse the same intent.
        return DB::transaction(function () use ($order, $user) {
            $order = ApparelOrder::lockForUpdate()->findOrFail($order->id);
            abort_if($order->payment_flow === 'hosted', 422, 'Continue payment through the website checkout for this order.');
            abort_unless($order->api_checkout && $order->status === 'pending' && $order->payment_status === 'pending', 422, 'This order is not awaiting payment.');
            $user = $user->newQuery()->lockForUpdate()->findOrFail($user->id);
            if (! $user->stripe_customer_id) {
                $customer = $this->stripe('POST', 'customers', ['email' => $user->email, 'name' => $user->name, 'metadata' => ['user_id' => $user->id]], ['Idempotency-Key' => 'apparel-customer-'.$user->id]);
                $user->forceFill(['stripe_customer_id' => $customer['id']])->save();
            }
            $intent = $order->payment_intent_id
                ? $this->stripe('GET', 'payment_intents/'.$order->payment_intent_id)
                : $this->stripe('POST', 'payment_intents', [
                    'amount' => (int) round((float) $order->total * 100), 'currency' => strtolower($order->currency),
                    'customer' => $user->stripe_customer_id, 'automatic_payment_methods' => ['enabled' => 'true'],
                    'setup_future_usage' => 'off_session', 'receipt_email' => $order->email,
                    'metadata' => ['purchase_type' => 'apparel', 'order_id' => (string) $order->id, 'user_id' => (string) $user->id],
                ], ['Idempotency-Key' => 'apparel-order-'.$order->number]);
            $order->update(['payment_intent_id' => $intent['id']]);

            return ['intent' => $intent, 'customer' => $user->stripe_customer_id];
        });
    }

    public function hostedCheckout(ApparelOrder $order): ?string
    {
        abort_unless(config('services.stripe.secret'), 503, 'Online payment is not configured.');
        // Commit the flow before contacting Stripe. A lost response must not allow
        // another payment through the mobile PaymentIntent flow.
        DB::transaction(function () use ($order) {
            $locked = ApparelOrder::lockForUpdate()->findOrFail($order->id);
            abort_unless($locked->api_checkout && $locked->status === 'pending' && $locked->payment_status === 'pending', 422, 'This order is not awaiting payment.');
            abort_if($locked->payment_intent_id && $locked->payment_flow !== 'hosted', 422, 'Resume the existing payment for this order.');
            $locked->update(['payment_flow' => 'hosted']);
        });

        return DB::transaction(function () use ($order) {
            $order = ApparelOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->payment_status === 'paid') return null;
            abort_unless($order->status === 'pending', 422, 'This order is not awaiting payment.');
            if ($order->checkout_session_id) {
                $session = $this->stripe('GET', 'checkout/sessions/'.$order->checkout_session_id);
                if (($session['status'] ?? '') === 'complete') {
                    $this->completeCheckout($order, $session);
                    return null;
                }
                if (($session['status'] ?? '') === 'open') return $session['url'];
                abort_unless(($session['status'] ?? '') === 'expired', 422, 'Please check this order’s payment status before retrying.');
            }
            $metadata = ['purchase_type' => 'apparel', 'order_id' => (string) $order->id, 'user_id' => (string) $order->user_id];
            $session = $this->stripe('POST', 'checkout/sessions', [
                'mode' => 'payment', 'payment_method_types' => ['card'],
                'success_url' => route('apparel.orders.checkout-return', $order),
                'cancel_url' => route('apparel.orders.show', $order).'?payment_cancelled=1',
                'customer_email' => $order->email, 'client_reference_id' => (string) $order->id,
                'metadata' => $metadata, 'payment_intent_data' => ['metadata' => $metadata],
                'line_items' => $order->items->map(fn ($item) => [
                    'quantity' => $item->quantity,
                    'price_data' => ['currency' => strtolower($order->currency), 'unit_amount' => (int) round((float) $item->price * 100),
                        'product_data' => ['name' => $item->name, 'description' => $item->options ?: 'Apparel order item']],
                ])->values()->all(),
            ], ['Idempotency-Key' => 'apparel-hosted-'.$order->number.($order->checkout_session_id ? '-'.$order->checkout_session_id : '')]);
            $order->update(['checkout_session_id' => $session['id']]);
            return $session['url'];
        });
    }

    public function completeCheckout(ApparelOrder $order, array $session): ApparelOrder
    {
        return DB::transaction(function () use ($order, $session) {
            $order = ApparelOrder::lockForUpdate()->findOrFail($order->id);
            abort_unless($order->payment_flow === 'hosted' && $order->checkout_session_id === ($session['id'] ?? null)
                && ($session['mode'] ?? '') === 'payment' && ($session['payment_status'] ?? '') === 'paid'
                && ($session['metadata']['purchase_type'] ?? '') === 'apparel'
                && (string) ($session['metadata']['order_id'] ?? '') === (string) $order->id
                && (string) ($session['metadata']['user_id'] ?? '') === (string) $order->user_id
                && ($session['amount_total'] ?? -1) === (int) round((float) $order->total * 100)
                && ($session['currency'] ?? '') === strtolower($order->currency)
                && is_string($session['payment_intent'] ?? null), 422, 'Payment is incomplete or does not match this order.');
            $intent = $this->stripe('GET', 'payment_intents/'.$session['payment_intent']);
            abort_if($order->payment_intent_id && $order->payment_intent_id !== $intent['id'], 422, 'Payment does not match this order.');
            $order->update(['payment_intent_id' => $intent['id']]);
            return $this->complete($order, $intent);
        });
    }

    public function complete(ApparelOrder $order, array $intent): ApparelOrder
    {
        return DB::transaction(function () use ($order, $intent) {
            $order = ApparelOrder::lockForUpdate()->findOrFail($order->id);
            if (! $order->api_checkout || ($intent['id'] ?? null) !== $order->payment_intent_id
                || ($intent['metadata']['purchase_type'] ?? null) !== 'apparel'
                || (string) ($intent['metadata']['order_id'] ?? '') !== (string) $order->id
                || (string) ($intent['metadata']['user_id'] ?? '') !== (string) $order->user_id
                || ($intent['currency'] ?? '') !== strtolower($order->currency)
                || ($intent['amount'] ?? -1) !== (int) round((float) $order->total * 100)
                || ($intent['amount_received'] ?? -1) !== (int) round((float) $order->total * 100)
                || ($intent['status'] ?? '') !== 'succeeded') {
                throw ValidationException::withMessages(['payment_intent_id' => 'Payment is incomplete or does not match this order.']);
            }
            if ($order->payment_status !== 'paid') {
                abort_if($order->status === 'cancelled', 409, 'This order was cancelled. Payment requires manual review.');
                $order->update(['payment_status' => 'paid', 'status' => 'processing', 'paid_at' => now()]);
            }

            return $order;
        });
    }
}
