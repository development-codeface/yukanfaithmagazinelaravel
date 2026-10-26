@extends('apparel.layout')
@section('title', 'Order #'.$order->id)
@section('content')
<h1 class="collection-title">Order #{{ $order->id }}</h1>
<p>Reference: {{ $order->number }}</p>
<p>{{ ucfirst($order->status) }} · Payment {{ $order->payment_status }} · {{ $order->created_at->format('M j, Y') }}</p>
@if($order->tracking_number)<p>Tracking number: {{ $order->tracking_number }}</p>@endif
@foreach($order->items as $item)<article class="order-card"><div>{{ $item->name }}<small>{{ $item->options }}</small><small>{{ $item->quantity }} × ${{ $item->price }}</small></div><strong>${{ number_format($item->quantity * $item->price, 2) }}</strong></article>@endforeach
<p class="cart-total">Total: {{ $order->currency }} ${{ $order->total }}</p>
<h2>Delivery details</h2><p>{{ $order->name }} · {{ $order->email }} · {{ $order->phone }}</p><p class="prose">{{ $order->address }}</p>
@if($order->notes)<p>{{ $order->notes }}</p>@endif
<a href="{{ route('apparel.orders.index') }}">Back to order history</a>
@endsection
