<?php

namespace App\Http\Controllers;

use App\Models\ApparelCategory;
use App\Models\ApparelOrder;
use App\Models\ApparelProduct;
use App\Models\User;
use App\Services\ApparelPaymentService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApparelOrderController extends Controller
{
    private function viewData(): array
    {
        return ['categories' => ApparelCategory::where('status', true)->orderBy('sort_order')->get()];
    }

    public function checkout(Request $request)
    {
        if (! $request->session()->get('apparel_cart')) {
            return redirect()->route('apparel.cart');
        }
        $items = app(ApparelStoreController::class)->cartItems($request);
        if ($items->count() !== count($request->session()->get('apparel_cart'))) {
            return redirect()->route('apparel.cart')->withErrors(['cart' => 'Please review your cart; some items are no longer available.']);
        }
        if (! $request->session()->has('apparel_checkout_token')) {
            $request->session()->put('apparel_checkout_token', (string) Str::uuid());
        }

        return response()->view('apparel.checkout', $this->viewData() + ['items' => $items])->header('Cache-Control', 'private, no-store');
    }

    public function account(Request $request)
    {
        if ($request->user()) {
            return response()->json(['authenticated' => true, 'csrf_token' => $request->session()->token()]);
        }
        $data = $request->validate([
            'first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255', 'password' => 'required|string|max:255',
        ]);
        $email = strtolower($data['email']);
        $existing = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing) {
            if ($existing->trashed() || ! Auth::attempt(['email' => $existing->email, 'password' => $data['password']])) {
                throw ValidationException::withMessages(['password' => 'Unable to sign in. Check your password or use the password reset link.']);
            }
        } else {
            $request->validate(['password' => ['confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()]]);
            $user = new User(['name' => $data['first_name'].' '.$data['last_name'], 'email' => $email, 'password' => $data['password']]);
            if (Schema::hasColumn('users', 'role')) {
                $user->forceFill(['role' => 'user']);
            }
            $user->save();
            Auth::login($user);
            event(new \Illuminate\Auth\Events\Registered($user));
        }
        $request->session()->regenerate();

        return response()->json(['authenticated' => true, 'csrf_token' => $request->session()->token()]);
    }

    public function store(Request $request)
    {
        if ($request->has('first_name')) {
            $shipping = $request->validate([
                'first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100',
                'country' => 'required|string|max:100', 'street' => 'required|string|max:255',
                'street2' => 'nullable|string|max:255', 'city' => 'required|string|max:100',
                'region' => 'required|string|max:100', 'postal_code' => 'required|string|max:30',
            ]);
            $request->merge([
                'name' => $shipping['first_name'].' '.$shipping['last_name'],
                'address' => implode("\n", array_filter([$shipping['street'], $shipping['street2'] ?? null, $shipping['city'].', '.$shipping['region'].' '.$shipping['postal_code'], $shipping['country']])),
            ]);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50', 'address' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000', 'checkout_token' => 'required|uuid',
        ]);
        $existing = ApparelOrder::where('checkout_token', $data['checkout_token'])->where('user_id', $request->user()->id)->first();
        if ($existing) {
            return redirect()->route('apparel.orders.show', $existing);
        }
        abort_unless($data['checkout_token'] === $request->session()->get('apparel_checkout_token'), 419);
        $cart = $request->session()->get('apparel_cart', []);
        if (! $cart) {
            return redirect()->route('apparel.cart');
        }
        try {
            $order = DB::transaction(function () use ($data, $cart, $request) {
                $order = ApparelOrder::create($data + ['user_id' => $request->user()->id, 'number' => (string) Str::uuid(), 'total' => 0, 'api_checkout' => true, 'currency' => strtoupper(config('services.stripe.currency', 'inr'))]);
                $total = 0;
                foreach (collect($cart)->sortBy('product_id') as $item) {
                    $product = ApparelProduct::where('status', 'published')->whereHas('category', fn ($q) => $q->where('status', true))->lockForUpdate()->find($item['product_id']);
                    $variant = $product && $item['variant_id'] ? $product->variants()->where('status', true)->lockForUpdate()->find($item['variant_id']) : null;
                    $stock = $variant ?? $product;
                    if (! $product || ($item['variant_id'] && ! $variant) || (! $variant && $product->variants()->exists()) || $item['quantity'] < 1 || $item['quantity'] > $stock->stock) {
                        throw ValidationException::withMessages(['cart' => 'An item is unavailable or has insufficient stock. Please update your cart.']);
                    }
                    $price = $variant?->price ?? $product->price;
                    $cents = (int) round((float) $price * 100);
                    $total += $cents * $item['quantity'];
                    $order->items()->create([
                        'product_id' => $product->id, 'variant_id' => $variant?->id, 'name' => $product->name,
                        'options' => $variant ? $variant->optionValues->map(fn ($v) => $v->option->name.': '.$v->value)->implode(', ') ?: implode(' / ', array_filter([$variant->color_name, $variant->size, $variant->option_value])) : null,
                        'sku' => $variant?->sku ?? $product->sku, 'price' => $cents / 100, 'quantity' => $item['quantity'],
                    ]);
                    $stock->decrement('stock', $item['quantity']);
                }
                if ($total < 1) {
                    throw ValidationException::withMessages(['cart' => 'Order total must be greater than zero.']);
                }
                $order->update(['total' => $total / 100]);

                return $order;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            // A concurrent submission may already have committed this checkout.
            $order = ApparelOrder::where('checkout_token', $data['checkout_token'])->where('user_id', $request->user()->id)->first();
            if (! $order) {
                throw $exception;
            }
        }
        $request->session()->forget(['apparel_cart', 'apparel_checkout_token']);

        return redirect()->route('apparel.orders.show', $order)->with('success', 'Order created. Complete your payment below.');
    }

    public function index(Request $request)
    {
        return response()->view('apparel.orders', $this->viewData() + ['orders' => ApparelOrder::where('user_id', $request->user()->id)->latest()->paginate(15)])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, ApparelOrder $order)
    {
        abort_unless($order->user_id == $request->user()->id, 404);

        return response()->view('apparel.order', $this->viewData() + ['order' => $order->load('items')])->header('Cache-Control', 'private, no-store');
    }

    public function paymentIntent(Request $request, ApparelOrder $order, ApparelPaymentService $payments)
    {
        abort_unless($order->user_id == $request->user()->id, 404);
        abort_unless(config('services.stripe.key'), 503, 'Online payment is not configured.');
        try {
            $sheet = $payments->sheet($order, $request->user());
        } catch (GuzzleException $e) {
            report($e);
            return response()->json(['message' => 'Unable to start payment. Please retry this order.'], 502);
        }
        return response()->json([
            'publishable_key' => config('services.stripe.key'),
            'client_secret' => $sheet['intent']['client_secret'],
            'payment_intent_id' => $sheet['intent']['id'],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function confirmPayment(Request $request, ApparelOrder $order, ApparelPaymentService $payments)
    {
        abort_unless($order->user_id == $request->user()->id, 404);
        $data = $request->validate(['payment_intent_id' => 'required|string|max:255']);
        abort_unless($order->payment_intent_id && $order->payment_intent_id === $data['payment_intent_id'], 422, 'Payment does not match this order.');
        try {
            $payments->complete($order, $payments->stripe('GET', 'payment_intents/'.$order->payment_intent_id));
        } catch (GuzzleException $e) {
            report($e);
            return response()->json(['message' => 'Unable to verify payment. Please retry verification.'], 502);
        }
        return response()->json(['redirect' => route('apparel.orders.show', $order)]);
    }

    public function paymentReturn(Request $request, ApparelOrder $order, ApparelPaymentService $payments)
    {
        abort_unless($order->user_id == $request->user()->id, 404);
        abort_unless($order->payment_intent_id, 422);
        try {
            $intent = $payments->stripe('GET', 'payment_intents/'.$order->payment_intent_id);
            if (($intent['status'] ?? '') !== 'succeeded') {
                return redirect()->route('apparel.orders.show', $order)->withErrors(['payment' => 'Payment is not yet complete. You can retry or check its status below.']);
            }
            $payments->complete($order, $intent);
        } catch (GuzzleException $e) {
            report($e);
            return redirect()->route('apparel.orders.show', $order)->withErrors(['payment' => 'Unable to verify payment. Please check again below.']);
        } catch (ValidationException $e) {
            return redirect()->route('apparel.orders.show', $order)->withErrors($e->errors());
        }
        return redirect()->route('apparel.orders.show', $order)->with('success', 'Payment received. Thank you for your order.');
    }

    public function cancel(Request $request, ApparelOrder $order, ApparelPaymentService $payments)
    {
        abort_unless($order->user_id == $request->user()->id, 404);
        $payments->cancel($order);
        return redirect()->route('apparel.orders.show', $order)->with('success', 'Order cancelled.');
    }
}
