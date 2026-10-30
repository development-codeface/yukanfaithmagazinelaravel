<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApparelOrderResource;
use App\Models\ApparelOrder;
use App\Services\ApparelCartService;
use App\Services\ApparelPaymentService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApparelOrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['per_page' => 'nullable|integer|min:1|max:50']);

        return ApparelOrderResource::collection(ApparelOrder::where('user_id', $request->user()->id)->with('items')->latest()->paginate($request->integer('per_page', 15)));
    }

    public function show(Request $request, ApparelOrder $order)
    {
        $this->owner($request, $order);

        return new ApparelOrderResource($order->load('items'));
    }

    public function store(Request $request, ApparelCartService $service)
    {
        $data = $request->validate([
            'checkout_token' => 'required|uuid', 'name' => 'required|string|max:255',
            'email' => 'required|email|max:255', 'phone' => 'required|string|max:50',
            'address' => 'required|string|max:2000', 'notes' => 'nullable|string|max:2000',
        ]);
        $order = DB::transaction(function () use ($request, $data, $service) {
            $cart = $service->cart($request->user()->id);
            $existing = ApparelOrder::where('checkout_token', $data['checkout_token'])->first();
            if ($existing) {
                abort_unless($existing->user_id == $request->user()->id, 409, 'Checkout token already used.');

                return $existing;
            }
            if (! $cart->items) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }
            $order = ApparelOrder::create($data + ['user_id' => $request->user()->id, 'number' => (string) Str::uuid(), 'total' => 0, 'currency' => strtoupper(config('services.stripe.currency', 'inr')), 'api_checkout' => true]);
            $total = 0;
            foreach (collect($cart->items)->sortBy('product_id') as $item) {
                [$product, $variant, $cents] = $service->resolve($item, true);
                $total += $cents * $item['quantity'];
                $order->items()->create([
                    'product_id' => $product->id, 'variant_id' => $variant?->id, 'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku, 'price' => $cents / 100, 'quantity' => $item['quantity'],
                    'options' => $variant ? $variant->optionValues->map(fn ($v) => $v->option->name.': '.$v->value)->implode(', ') ?: implode(' / ', array_filter([$variant->color_name, $variant->size, $variant->option_value])) : null,
                ]);
                ($variant ?? $product)->decrement('stock', $item['quantity']);
            }
            abort_if($total < 1, 422, 'Order total must be greater than zero.');
            $order->update(['total' => $total / 100]);
            $cart->update(['items' => []]);

            return $order;
        });

        return (new ApparelOrderResource($order->refresh()->load('items')))->response()->setStatusCode(201);
    }

    public function paymentIntent(Request $request, ApparelOrder $order, ApparelPaymentService $payments)
    {
        $this->owner($request, $order);
        try {
            $sheet = $payments->sheet($order, $request->user());
            // Persist the intent before requesting the short-lived ephemeral key.
            $key = $payments->stripe('POST', 'ephemeral_keys', ['customer' => $sheet['customer']], ['Stripe-Version' => '2024-06-20']);
        } catch (GuzzleException $e) {
            report($e);

            return response()->json(['message' => 'Unable to start Stripe payment. Retry this order.'], 502);
        }
        $intent = $sheet['intent'];

        return response()->json([
            'requires_payment' => true, 'order_id' => $order->id,
            'publishable_key' => config('services.stripe.key'), 'publishableKey' => config('services.stripe.key'),
            'client_secret' => $intent['client_secret'], 'paymentIntent' => $intent['client_secret'],
            'payment_intent_id' => $intent['id'], 'paymentIntentId' => $intent['id'],
            'customer' => $sheet['customer'], 'ephemeralKey' => $key['secret'],
            'amount' => $intent['amount'], 'currency' => $intent['currency'],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function cancel(Request $request, ApparelOrder $order, ApparelPaymentService $payments)
    {
        $this->owner($request, $order);
        $order = $payments->cancel($order);

        return new ApparelOrderResource($order->load('items'));
    }

    public function confirmPayment(Request $request, ApparelOrder $order, ApparelPaymentService $payments)
    {
        $this->owner($request, $order);
        $data = $request->validate(['payment_intent_id' => 'required|string|regex:/^pi_[a-zA-Z0-9_]+$/|max:255']);
        abort_unless($order->payment_intent_id === $data['payment_intent_id'], 422, 'Payment does not match this order.');
        try {
            $intent = $payments->stripe('GET', 'payment_intents/'.$data['payment_intent_id']);
        } catch (GuzzleException $e) {
            report($e);

            return response()->json(['message' => 'Unable to verify Stripe payment.'], 502);
        }

        return new ApparelOrderResource($payments->complete($order, $intent)->load('items'));
    }

    public function webhook(Request $request, ApparelPaymentService $payments)
    {
        $secret = config('services.stripe.apparel_webhook_secret');
        abort_unless($secret, 503, 'Apparel webhook is not configured.');
        $parts = [];
        foreach (explode(',', $request->header('Stripe-Signature', '')) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) === 2) {
                $parts[$pair[0]][] = $pair[1];
            }
        }
        $timestamp = $parts['t'][0] ?? '';
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300 && collect($parts['v1'] ?? [])->contains(fn ($signature) => hash_equals($expected, $signature)), 400, 'Invalid signature.');
        $event = $request->json()->all();
        if (($event['type'] ?? '') === 'payment_intent.succeeded' && ($event['data']['object']['metadata']['purchase_type'] ?? '') === 'apparel') {
            $intent = $event['data']['object'];
            $order = ApparelOrder::where('payment_intent_id', $intent['id'])->first();
            // Stripe retries if the intent has not yet been persisted locally.
            abort_unless($order, 409, 'Order payment is not ready.');
            $payments->complete($order, $intent);
        }

        return response()->json(['received' => true]);
    }

    private function owner(Request $request, ApparelOrder $order): void
    {
        abort_unless($order->user_id == $request->user()->id, 404);
    }
}
