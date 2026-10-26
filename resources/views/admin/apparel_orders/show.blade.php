@extends('layouts.admin')
@section('content')
<div class="card"><div class="card-header">Order #{{ $order->id }} · {{ $order->number }}</div><div class="card-body">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p>{{ $order->name }} · {{ $order->email }} · {{ $order->phone }}</p><p style="white-space:pre-line">{{ $order->address }}</p><p>Customer notes: {{ $order->notes }}</p>
<p>Payment: {{ $order->payment_status }}. No online payment collected.</p>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Product / options</th><th>SKU</th><th>Quantity</th><th>Unit price</th><th>Subtotal</th></tr></thead><tbody>
@foreach($order->items as $item)<tr><td>{{ $item->name }}<br>{{ $item->options }}</td><td>{{ $item->sku }}</td><td>{{ $item->quantity }}</td><td>{{ $item->price }}</td><td>{{ number_format($item->quantity * $item->price, 2) }}</td></tr>@endforeach
</tbody></table></div><p><strong>Total: {{ $order->currency }} {{ $order->total }}</strong></p>
<form method="post" action="{{ route('admin.apparel-orders.update', $order) }}">@csrf @method('PATCH')
<div class="form-group"><label>Status</label><select class="form-control" name="status">@foreach(['pending','processing','shipped','completed','cancelled'] as $status)<option @selected(old('status', $order->status) === $status)>{{ $status }}</option>@endforeach</select></div>
<p>Pending → processing → shipped → completed. Cancelling a pending or processing order restores stock once. Cancelled orders cannot be reopened.</p>
<div class="form-group"><label>Tracking number</label><input class="form-control" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}" maxlength="255"></div>
<div class="form-group"><label>Internal notes</label><textarea class="form-control" name="admin_notes" maxlength="5000">{{ old('admin_notes', $order->admin_notes) }}</textarea></div>
<button class="btn btn-primary">Save order</button> <a href="{{ route('admin.apparel-orders.index') }}">Back to orders</a></form>
</div></div>
@endsection
