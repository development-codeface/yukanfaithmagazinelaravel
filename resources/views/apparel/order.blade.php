@extends('apparel.layout')
@section('title', 'Order #'.$order->id)
@section('content')
<h1 class="collection-title">Order #{{ $order->id }}</h1>
<p>Reference: {{ $order->number }}</p>
<p>{{ ucfirst($order->status) }} · Payment {{ $order->payment_status }} · {{ $order->created_at->format('M j, Y') }}</p>
@if($order->tracking_number)<p>Tracking number: {{ $order->tracking_number }}</p>@endif
@foreach($order->items as $item)<article class="order-card"><div>{{ $item->name }}<small>{{ $item->options }}</small><small>{{ $item->quantity }} × {{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $item->price }}</small></div><strong>{{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ number_format($item->quantity * $item->price, 2) }}</strong></article>@endforeach
<p class="cart-total">Total: {{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $order->total }}</p>
@if($order->payment_status === 'paid')
<div class="notice" role="status">Payment received. Thank you for your order.</div>
@elseif($order->status === 'pending' && $order->api_checkout)
<section class="order-payment" aria-labelledby="payment-heading">
    <h2 id="payment-heading">Complete your payment</h2>
    @if(config('services.stripe.key') && config('services.stripe.secret'))
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
    @if(!$order->payment_intent_id)
    <form method="post" action="{{ route('apparel.orders.cancel', $order) }}" id="cancel-order">@csrf<button type="submit" class="cancel-order">Cancel order</button></form>
    @else
    <p>For cancellation after starting payment, please contact the store.</p>
    @endif
    <noscript>Please enable JavaScript to pay online.</noscript>
</section>
@endif
<h2>Delivery details</h2><p>{{ $order->name }} · {{ $order->email }} · {{ $order->phone }}</p><p class="prose">{{ $order->address }}</p>
@if($order->notes)<p>{{ $order->notes }}</p>@endif
<a href="{{ route('apparel.orders.index') }}">Back to order history</a>
@endsection
@push('scripts')
@if($order->status === 'pending' && $order->payment_status === 'pending' && $order->api_checkout && config('services.stripe.key') && config('services.stripe.secret'))
<script src="https://js.stripe.com/v3/" defer></script>
<script src="{{ asset('js/apparel-payment.js') }}" defer></script>
@endif
@endpush
