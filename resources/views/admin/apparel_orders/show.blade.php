@extends('layouts.admin')

@section('content')
<div class="card order-details">
    <div class="card-header"><div><h4 class="mb-1">Order #{{ $order->id }}</h4><small class="text-muted">Reference: {{ $order->number }}</small></div></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <div class="order-summary mb-4">
            <div><h5>Customer</h5><strong>{{ $order->name }}</strong><div>{{ $order->email }}</div><div>{{ $order->phone }}</div></div>
            <div><h5>Delivery address</h5><div style="white-space:pre-line">{{ $order->address ?: 'Not provided' }}</div></div>
            <div><h5>Payment</h5><div>{{ ucfirst($order->payment_status) }}</div><h5 class="mt-3">Customer notes</h5><div style="white-space:pre-line">{{ $order->notes ?: 'None' }}</div></div>
        </div>

        <h5>Order items</h5>
        <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Product / options</th><th>SKU</th><th>Quantity</th><th>Unit price</th><th>Subtotal</th></tr></thead><tbody>
            @foreach($order->items as $item)<tr><td>{{ $item->name }}<br>{{ $item->options }}</td><td>{{ $item->sku }}</td><td>{{ $item->quantity }}</td><td>{{ $item->price }}</td><td>{{ number_format($item->quantity * $item->price, 2) }}</td></tr>@endforeach
        </tbody></table></div>
        <p class="order-total"><strong>Total: {{ $order->currency }} {{ $order->total }}</strong></p>

        <form method="post" action="{{ route('admin.apparel-orders.update', $order) }}" class="order-update">@csrf @method('PATCH')
            <h5>Manage order</h5>
            <div class="form-group"><label for="order-status">Status</label><select id="order-status" class="form-control" name="status">@foreach(['pending','processing','shipped','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <p class="text-muted">Pending → processing → shipped → completed. Cancelling a pending or processing order restores stock once. Cancelled orders cannot be reopened.</p>
            <div class="form-group"><label for="order-notes">Internal notes</label><textarea id="order-notes" class="form-control" name="admin_notes" rows="4" maxlength="5000">{{ old('admin_notes', $order->admin_notes) }}</textarea></div>
            <div class="order-actions"><button class="btn btn-primary" type="submit">Save order</button><a class="btn btn-outline-secondary" href="{{ route('admin.apparel-orders.index') }}">Back to orders</a></div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
.order-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; padding: 22px; background: #f8f4f3; border: 1px solid #eadfdd; border-radius: 10px; overflow-wrap: anywhere; }
.order-summary h5, .order-update h5 { color: #3c3b57; }
.order-total { text-align: right; font-size: 1.1rem; }
.order-update { margin-top: 24px; padding-top: 24px; border-top: 1px solid #eadfdd; }
.order-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.order-actions .btn { margin-bottom: 0; }
@media (max-width: 767px) { .order-summary { grid-template-columns: 1fr; } }
</style>
@endsection
