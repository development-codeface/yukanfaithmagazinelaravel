@extends('layouts.admin')
@section('content')
<div class="card"><div class="card-header">Apparel orders</div><div class="card-body">
<form method="get" class="mb-3"><input name="search" placeholder="Reference, customer or email" value="{{ request('search') }}"><select name="status"><option value="">All statuses</option>@foreach(['pending','processing','shipped','completed','cancelled'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select><button class="btn btn-primary">Filter</button></form>
<p>Open an order to update its status, shipment tracking number, and internal notes.</p>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th>Actions</th></tr></thead><tbody>
@forelse($orders as $order)<tr><td><a href="{{ route('admin.apparel-orders.show', $order) }}">#{{ $order->id }}</a></td><td>{{ $order->created_at }}</td><td>{{ $order->name }}<br>{{ $order->email }}</td><td>{{ $order->status }}</td><td>{{ $order->payment_status }}</td><td>{{ $order->currency }} {{ $order->total }}</td><td><a class="btn btn-danger btn-sm" href="{{ route('admin.apparel-orders.show', $order) }}">Manage order</a></td></tr>@empty<tr><td colspan="7">No orders found.</td></tr>@endforelse
</tbody></table></div>{{ $orders->links() }}</div></div>
@endsection
