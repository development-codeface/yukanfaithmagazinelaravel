@extends('layouts.admin')
@section('content')
<div class="card"><div class="card-header">Apparel orders</div><div class="card-body">
<form method="get" class="mb-4">
    <div class="row align-items-end">
        <div class="col-md-5 mb-3"><label for="order-search">Search orders</label><input id="order-search" class="form-control" name="search" placeholder="Reference, customer or email" value="{{ request('search') }}"></div>
        <div class="col-md-3 mb-3"><label for="order-status">Status</label><select id="order-status" class="form-control" name="status"><option value="">All statuses</option>@foreach(['pending','processing','shipped','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-auto mb-3 order-filter-actions"><button class="btn btn-primary" type="submit">Apply filters</button><a class="btn btn-outline-secondary" href="{{ route('admin.apparel-orders.index') }}">Reset</a></div>
    </div>
</form>
<p class="text-muted">Open an order to update its status and internal notes.</p>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th>Actions</th></tr></thead><tbody>
@forelse($orders as $order)<tr><td><a href="{{ route('admin.apparel-orders.show', $order) }}">#{{ $order->id }}</a></td><td>{{ $order->created_at }}</td><td>{{ $order->name }}<br>{{ $order->email }}</td><td>{{ $order->status }}</td><td>{{ $order->payment_status }}</td><td>{{ $order->currency }} {{ $order->total }}</td><td><a class="btn btn-danger btn-sm" href="{{ route('admin.apparel-orders.show', $order) }}">Manage order</a></td></tr>@empty<tr><td colspan="7">No orders found.</td></tr>@endforelse
</tbody></table></div>{{ $orders->links() }}</div></div>
@endsection
@section('styles')
<style>
.order-filter-actions { display: flex; align-items: center; gap: 8px; }
.order-filter-actions .btn { margin-bottom: 0; white-space: nowrap; }
</style>
@endsection
