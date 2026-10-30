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
            abort_unless($order->status === 'pending' && $order->payment_status === 'pending' && ! $order->payment_intent_id, 422, 'An order with a payment attempt requires staff assistance to cancel.');
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
