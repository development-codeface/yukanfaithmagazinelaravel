@extends('apparel.layout')
@section('title', 'Order history')
@section('content')
<h1 class="collection-title">Order history</h1>
@forelse($orders as $order)
<article class="order-card"><a href="{{ route('apparel.orders.show', $order) }}">Order #{{ $order->id }}</a><span>{{ $order->created_at->format('M j, Y') }} · {{ ucfirst($order->status) }} · Payment {{ $order->payment_status }}</span><strong>{{ \App\Support\ApparelMoney::prefix($order->currency) }}{{ $order->total }}</strong></article>
@empty<p>You have no orders yet. <a href="{{ route('apparel.index') }}">Browse products</a></p>@endforelse
{{ $orders->links() }}
@endsection
