@extends('apparel.layout')
@section('title', 'Order history')
@section('content')
<div class="order-history">
<header class="history-heading"><div><p class="order-eyebrow">YOUR ACCOUNT</p><h1>My orders</h1><p>Track your purchases and find all your order details here.</p></div><a class="history-shop" href="{{ route('apparel.index') }}">Continue shopping &rarr;</a></header>
@forelse($orders as $order)
<article class="history-card" aria-labelledby="history-order-{{ $order->id }}">
<header class="history-card-header"><div><h2 id="history-order-{{ $order->id }}"><a href="{{ route('apparel.orders.show', $order) }}">Order #{{ $order->id }}</a></h2><p>Placed {{ $order->created_at->format('M j, Y') }}</p></div><div class="history-statuses"><span class="history-status">Order: {{ ucfirst($order->status) }}</span><span class="history-status {{ $order->payment_status === 'paid' ? 'paid' : 'unpaid' }}">Payment: {{ ucfirst($order->payment_status) }}</span></div></header>
<div class="history-items">
@forelse($order->items as $item)
<div class="history-item"><span class="history-quantity" aria-label="Quantity {{ $item->quantity }}">{{ $item->quantity }}&times;</span><div><h3>{{ $item->name }}</h3>@if($item->options)<p>{{ $item->options }}</p>@endif<small>Qty {{ $item->quantity }} &middot; {{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $item->price }} each</small></div><strong>{{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ number_format($item->quantity * $item->price, 2) }}</strong></div>
@empty<p>Item details are unavailable for this order.</p>@endforelse
</div>
<footer class="history-card-footer"><div><span>Order total</span><strong>{{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $order->total }}</strong></div><a class="history-details" href="{{ route('apparel.orders.show', $order) }}">View order details &rarr;</a></footer>
@if($order->tracking_number)<p class="history-tracking">Tracking number: {{ $order->tracking_number }}</p>@endif
</article>
@empty
<section class="history-empty"><h2>Your orders will appear here</h2><p>Find something you love and start your first order.</p><a class="history-details" href="{{ route('apparel.index') }}">Explore products &rarr;</a></section>
@endforelse
{{ $orders->links() }}
</div>
@endsection
