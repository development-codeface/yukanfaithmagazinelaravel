@extends('apparel.layout')
@section('title', 'Order #'.$order->id)
@section('content')
<div class="order-page"><a class="order-back" href="{{ route('apparel.orders.index') }}">&larr; My orders</a><header class="order-heading"><p class="order-eyebrow">YUKANFAITH APPAREL</p><h1 class="collection-title">{{ $order->payment_status === 'paid' ? 'Your order is confirmed' : ($order->status === 'cancelled' ? 'Order cancelled' : 'Finish your payment') }}</h1>
<span class="order-badge">Order #{{ $order->id }}</span>
<p class="order-reference">Reference: {{ $order->number }}</p>
<p>{{ ucfirst($order->status) }} &middot; Payment {{ $order->payment_status }} &middot; {{ $order->created_at->format('M j, Y') }}</p>
@if($order->tracking_number)<p>Tracking number: {{ $order->tracking_number }}</p>@endif
</header><div class="order-grid"><section class="order-panel"><h2>Order summary</h2>@foreach($order->items as $item)<article class="order-card"><div>{{ $item->name }}<small>{{ $item->options }}</small><small>{{ $item->quantity }} &times; {{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $item->price }}</small></div><strong>{{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ number_format($item->quantity * $item->price, 2) }}</strong></article>@endforeach
<p class="cart-total">Total: {{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $order->total }}</p>
</section><aside class="order-sidebar">@if($order->payment_status === 'paid')
<div class="notice" role="status">Payment received. Thank you for your order.</div>
@elseif($order->status === 'pending' && $order->api_checkout)
<section class="order-payment" aria-labelledby="payment-heading">
    <h2 id="payment-heading">Complete your payment</h2>
    @if(config('services.stripe.secret') && (!$order->payment_intent_id || $order->payment_flow === 'hosted'))
        @if(request('payment_cancelled'))<p class="notice" role="status">Payment was not completed. Your order is saved and you can continue below.</p>@endif
        <p>Your delivery details are saved. Continue to secure payment to complete your order.</p>
        <form method="post" action="{{ route('apparel.orders.checkout', $order) }}">@csrf<button class="checkout-button" type="submit">{{ $order->checkout_session_id ? 'Resume secure payment' : 'Pay now' }} &middot; {{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $order->total }}</button></form>
        @if($order->checkout_session_id)<p><a href="{{ route('apparel.orders.checkout-return', $order) }}">Already paid? Check payment status</a></p>@endif
    @elseif(config('services.stripe.key') && config('services.stripe.secret'))
    <form id="order-payment" data-start-url="{{ route('apparel.orders.payment-intent', $order) }}" data-confirm-url="{{ route('apparel.orders.confirm-payment', $order) }}" data-return-url="{{ route('apparel.orders.payment-return', $order) }}">
        @csrf
        <div id="payment-error" class="notice error" role="alert" hidden></div>
        <div id="payment-status" role="status" aria-live="polite"></div>
        <div id="payment-element"></div>
        <button type="button" id="start-payment" class="checkout-button">{{ $order->payment_intent_id ? 'Resume payment' : 'Pay now' }}</button>
        <button type="submit" id="submit-payment" class="checkout-button" hidden>Pay {{ $order->currency }} {{ $order->total }}</button>
        <button type="button" id="verify-payment" hidden>Check payment status</button>
    </form>
    @else
    <p>Online payment is temporarily unavailable. Please try again later.</p>
    @endif
    @if(!$order->payment_intent_id && $order->payment_flow !== 'hosted')
    <form method="post" action="{{ route('apparel.orders.cancel', $order) }}" id="cancel-order">@csrf<button type="submit" class="cancel-order">Cancel order</button></form>
    @else
    <p>For cancellation after starting payment, please contact the store.</p>
    @endif
    <noscript>Please enable JavaScript to pay online.</noscript>
</section>
@endif
</aside></div><section class="order-panel order-delivery"><h2>Delivery details</h2><p>{{ $order->name }} &middot; {{ $order->email }} &middot; {{ $order->phone }}</p><p class="prose">{{ $order->address }}</p>
@if($order->notes)<p>{{ $order->notes }}</p>@endif
</section></div>
@endsection
@push('scripts')
@if($order->status === 'pending' && $order->payment_status === 'pending' && $order->api_checkout && $order->payment_intent_id && $order->payment_flow !== 'hosted' && config('services.stripe.key') && config('services.stripe.secret'))
<script src="https://js.stripe.com/v3/" defer></script>
<script src="{{ asset('js/apparel-payment.js') }}" defer></script>
@endif
@endpush

