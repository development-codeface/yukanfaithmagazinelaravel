@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header"><h4>Magazine Delivery Requests</h4></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @include('admin.magazine_purchases.filters')
        <p class="text-muted">{{ $purchases->total() }} purchase(s) found.</p>
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>ID</th><th>Purchase date</th><th>Customer</th><th>Magazine</th><th>Format</th><th>Amount paid</th><th>Payment ID</th><th>Delivery address</th><th>Notification</th></tr></thead>
            <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->id }}</td>
                    <td>{{ $purchase->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ $purchase->user?->name }}<br>{{ $purchase->user?->email }}</td>
                    <td>{{ $purchase->magazine?->title ?? 'Deleted magazine' }}</td>
                    <td>{{ ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$purchase->delivery_type] }}</td>
                    <td>{{ strtoupper($purchase->currency ?: config('services.stripe.currency', 'usd')) }} {{ number_format((float) $purchase->amount_paid, 2) }}</td>
                    <td class="text-break">{{ $purchase->transaction_id ?: 'Not available' }}</td>
                    <td>@foreach($purchase->delivery_address ?? [] as $value)@if($value)<div>{{ $value }}</div>@endif @endforeach</td>
                    <td>
                        @if(in_array($purchase->delivery_type, ['print', 'both'], true))
                            @include('partials.delivery-review-status', ['reviewedAt' => $purchase->print_acknowledged_at, 'reviewUrl' => route('admin.magazine-purchases.acknowledge', $purchase)])
                        @else
                            <span class="text-muted">Not applicable</span>
                        @endif
                    </td>
                </tr>
            @empty <tr><td colspan="9">No magazine purchases match these filters.</td></tr> @endforelse
            </tbody>
        </table></div>
        {{ $purchases->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
